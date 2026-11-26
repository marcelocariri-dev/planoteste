<?php

namespace Src\Infrastructure\Mcp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Src\Application\IntegracaoMcp\Ports\ClienteMcpInterface;
use Src\Domain\IntegracaoMcp\Entities\ConexaoMcp;
use Src\Domain\IntegracaoMcp\ValueObjects\FerramentaMcp;
use Src\Domain\IntegracaoMcp\ValueObjects\ResultadoFerramenta;

/**
 * Cliente MCP mínimo para o transporte "Streamable HTTP" (JSON-RPC 2.0 via POST;
 * a resposta pode vir como application/json ou como text/event-stream).
 */
final class ClienteMcpHttp implements ClienteMcpInterface
{
    private const PROTOCOLO = '2025-06-18';

    private ?string $sessao = null;

    private string $protocoloNegociado = self::PROTOCOLO;

    private int $proximoId = 1;

    public function __construct(private readonly int $timeout = 60)
    {
    }

    public function listarFerramentas(ConexaoMcp $conexao): array
    {
        $info = $this->iniciar($conexao);

        try {
            $ferramentas = [];
            $cursor = null;
            $paginas = 0;
            do {
                $resultado = $this->requisitar($conexao, 'tools/list', $cursor ? ['cursor' => $cursor] : (object) []);
                foreach ($resultado['tools'] ?? [] as $t) {
                    $ferramentas[] = new FerramentaMcp((string) $t['name'], $t['description'] ?? null, (array) ($t['inputSchema'] ?? []));
                }
                $cursor = $resultado['nextCursor'] ?? null;
            } while ($cursor && ++$paginas < 20);

            return [
                'servidor' => ['nome' => $info['serverInfo']['name'] ?? null, 'versao' => $info['serverInfo']['version'] ?? null],
                'ferramentas' => $ferramentas,
            ];
        } finally {
            $this->encerrar($conexao);
        }
    }

    public function chamarFerramenta(ConexaoMcp $conexao, string $ferramenta, array $argumentos): ResultadoFerramenta
    {
        $this->iniciar($conexao);

        try {
            $r = $this->requisitar($conexao, 'tools/call', ['name' => $ferramenta, 'arguments' => (object) $argumentos]);

            $textos = [];
            foreach ($r['content'] ?? [] as $bloco) {
                if (($bloco['type'] ?? '') === 'text') {
                    $textos[] = (string) $bloco['text'];
                } elseif (($bloco['type'] ?? '') === 'resource' && isset($bloco['resource']['text'])) {
                    $textos[] = (string) $bloco['resource']['text'];
                }
            }

            return new ResultadoFerramenta($textos, is_array($r['structuredContent'] ?? null) ? $r['structuredContent'] : null, (bool) ($r['isError'] ?? false));
        } finally {
            $this->encerrar($conexao);
        }
    }

    private function iniciar(ConexaoMcp $conexao): array
    {
        $this->sessao = null;
        $resultado = $this->requisitar($conexao, 'initialize', [
            'protocolVersion' => self::PROTOCOLO,
            'capabilities' => (object) [],
            'clientInfo' => ['name' => 'plano-teste', 'version' => '1.0.0'],
        ]);
        $this->protocoloNegociado = $resultado['protocolVersion'] ?? self::PROTOCOLO;
        $this->notificar($conexao, 'notifications/initialized');

        return $resultado;
    }

    private function encerrar(ConexaoMcp $conexao): void
    {
        if ($this->sessao) {
            try {
                $this->http($conexao)->delete($conexao->url());
            } catch (\Throwable) {
                // servidor pode não suportar DELETE; sem problema
            }
            $this->sessao = null;
        }
    }

    private function requisitar(ConexaoMcp $conexao, string $metodo, array|object $params): array
    {
        $id = $this->proximoId++;
        $resposta = $this->http($conexao)->post($conexao->url(), ['jsonrpc' => '2.0', 'id' => $id, 'method' => $metodo, 'params' => $params]);

        if ($resposta->header('Mcp-Session-Id')) {
            $this->sessao = $resposta->header('Mcp-Session-Id');
        }

        if ($resposta->status() === 401 || $resposta->status() === 403) {
            throw new RuntimeException("acesso negado (HTTP {$resposta->status()}). Verifique o token/cabeçalhos.");
        }
        if ($resposta->failed()) {
            throw new RuntimeException("HTTP {$resposta->status()} em {$metodo}: " . mb_substr($resposta->body(), 0, 300));
        }

        $mensagem = $this->extrairMensagem($resposta, $id);

        if (isset($mensagem['error'])) {
            throw new RuntimeException(sprintf('erro MCP em %s: %s', $metodo, $mensagem['error']['message'] ?? json_encode($mensagem['error'])));
        }

        return (array) ($mensagem['result'] ?? []);
    }

    private function notificar(ConexaoMcp $conexao, string $metodo): void
    {
        $this->http($conexao)->post($conexao->url(), ['jsonrpc' => '2.0', 'method' => $metodo]);
    }

    private function extrairMensagem(Response $resposta, int $id): array
    {
        $tipo = strtolower((string) $resposta->header('Content-Type'));

        if (str_contains($tipo, 'text/event-stream')) {
            // cada evento SSE pode ter várias linhas "data:"; procura a resposta com o nosso id
            foreach (preg_split('/\R\R/', $resposta->body()) as $evento) {
                $dados = '';
                foreach (preg_split('/\R/', $evento) as $linha) {
                    if (str_starts_with($linha, 'data:')) {
                        $dados .= ltrim(substr($linha, 5));
                    }
                }
                $json = json_decode($dados, true);
                if (is_array($json) && ($json['id'] ?? null) === $id) {
                    return $json;
                }
            }
            throw new RuntimeException('resposta SSE sem a mensagem esperada.');
        }

        $json = $resposta->json();
        if (! is_array($json)) {
            throw new RuntimeException('resposta não é JSON-RPC: ' . mb_substr($resposta->body(), 0, 200));
        }
        if (array_is_list($json)) { // lote
            foreach ($json as $item) {
                if (($item['id'] ?? null) === $id) {
                    return $item;
                }
            }
        }

        return $json;
    }

    private function http(ConexaoMcp $conexao)
    {
        $cabecalhos = ['Accept' => 'application/json, text/event-stream', 'MCP-Protocol-Version' => $this->protocoloNegociado]
            + array_map('strval', $conexao->cabecalhos());
        if ($conexao->token()) {
            $cabecalhos['Authorization'] = 'Bearer ' . $conexao->token();
        }
        if ($this->sessao) {
            $cabecalhos['Mcp-Session-Id'] = $this->sessao;
        }

        return Http::withHeaders($cabecalhos)->asJson()->timeout($this->timeout)->connectTimeout(10);
    }
}
