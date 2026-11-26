<?php

namespace Src\Application\IntegracaoMcp\UseCases;

use Src\Application\CasoTeste\Services\CriadorCasosEmPlano;
use Src\Application\IntegracaoMcp\Ports\ClienteMcpInterface;
use Src\Domain\CasoTeste\Enums\OrigemCaso;
use Src\Domain\IntegracaoMcp\Entities\ConexaoMcp;
use Src\Domain\IntegracaoMcp\Repositories\ConexaoMcpRepositoryInterface;
use Src\Domain\IntegracaoMcp\Services\MapeadorCasosExternos;
use Src\Domain\IntegracaoMcp\ValueObjects\FerramentaMcp;
use Src\Domain\RegraNegocio\ValueObjects\CasoExtraido;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

/**
 * Integração com o servidor MCP oficial da empresa: busca casos de teste e importa para um plano.
 */
final class GerenciarIntegracaoMcp
{
    public function __construct(
        private readonly ConexaoMcpRepositoryInterface $conexoes,
        private readonly ClienteMcpInterface $cliente,
        private readonly MapeadorCasosExternos $mapeador,
        private readonly CriadorCasosEmPlano $criadorCasos,
    ) {
    }

    public function listar(): array
    {
        return array_map(fn (ConexaoMcp $c) => $c->toArray(), $this->conexoes->listar());
    }

    public function salvar(?int $id, array $d): array
    {
        $cabecalhos = $this->objeto($d['cabecalhos'] ?? [], 'Cabeçalhos');
        $argumentos = $this->objeto($d['argumentos_padrao'] ?? [], 'Argumentos padrão');

        if ($id) {
            $conexao = $this->buscar($id);
            $conexao->atualizar(
                (string) ($d['nome'] ?? ''), (string) ($d['url'] ?? ''),
                array_key_exists('token', $d) ? (string) ($d['token'] ?? '') : null,
                // cabeçalhos mascarados no front: se não vierem, mantém
                array_key_exists('cabecalhos', $d) ? $cabecalhos : $conexao->cabecalhos(),
                $d['ferramenta_casos'] ?? null, $argumentos, (bool) ($d['ativo'] ?? true),
            );
        } else {
            $conexao = ConexaoMcp::criar(
                (string) ($d['nome'] ?? ''), (string) ($d['url'] ?? ''), $d['token'] ?? null,
                $cabecalhos, $d['ferramenta_casos'] ?? null, $argumentos,
            );
        }

        return $this->conexoes->salvar($conexao)->toArray();
    }

    public function excluir(int $id): void
    {
        $this->buscar($id);
        $this->conexoes->excluir($id);
    }

    /** Handshake + tools/list. Registra o resultado do teste na conexão. */
    public function testar(int $id): array
    {
        $conexao = $this->buscar($id);

        try {
            $info = $this->cliente->listarFerramentas($conexao);
            $total = count($info['ferramentas']);
            $conexao->registrarTeste(true, sprintf('Conectado a %s. %d ferramenta(s) disponível(is).', $info['servidor']['nome'] ?? 'servidor MCP', $total));
            $this->conexoes->salvar($conexao);

            return [
                'conexao' => $conexao->toArray(),
                'servidor' => $info['servidor'],
                'ferramentas' => array_map(fn (FerramentaMcp $f) => $f->toArray(), $info['ferramentas']),
            ];
        } catch (\Throwable $e) {
            $conexao->registrarTeste(false, $e->getMessage());
            $this->conexoes->salvar($conexao);
            throw new DomainException('Falha ao conectar no servidor MCP: ' . $e->getMessage());
        }
    }

    /** tools/call na ferramenta de casos e conversão para candidatos (pré-visualização). */
    public function buscarCasos(int $id, ?string $ferramenta, ?array $argumentos): array
    {
        $conexao = $this->buscar($id);
        $conexao->garantirAtiva();

        $ferramenta = $ferramenta ?: $conexao->ferramentaCasos()
            ?? throw new DomainException('Informe qual ferramenta do MCP retorna os casos de teste.');

        try {
            $resultado = $this->cliente->chamarFerramenta($conexao, $ferramenta, $argumentos ?? $conexao->argumentosPadrao());
        } catch (DomainException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DomainException('Falha ao chamar a ferramenta MCP: ' . $e->getMessage());
        }

        $casos = $this->mapeador->mapear($resultado);

        return [
            'ferramenta' => $ferramenta,
            'total' => count($casos),
            'casos' => array_map(fn (CasoExtraido $c) => $c->toArray(), $casos),
            'texto_bruto' => mb_substr(implode("\n", $resultado->textos), 0, 20000),
        ];
    }

    /**
     * Importa casos (já revisados na tela) para um plano existente ou novo.
     *
     * @param array<int, array> $casos
     */
    public function importar(int $id, array $casos, ?int $planoId, ?array $novoPlano): array
    {
        $conexao = $this->buscar($id);
        $candidatos = array_map(fn (array $c) => CasoExtraido::deArray($c), $casos);

        return $this->criadorCasos->executar(
            $candidatos,
            OrigemCaso::MCP,
            fn (CasoExtraido $c) => $c->idExterno ? mb_substr("mcp:{$conexao->id()}:{$c->idExterno}", 0, 150) : null,
            $planoId,
            $novoPlano ? $novoPlano + ['descricao' => "Casos oficiais importados via MCP ({$conexao->nome()})."] : null,
        );
    }

    private function buscar(int $id): ConexaoMcp
    {
        return $this->conexoes->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Conexão MCP', $id);
    }

    private function objeto(mixed $valor, string $campo): array
    {
        if (is_string($valor)) {
            $valor = trim($valor) === '' ? [] : json_decode($valor, true);
        }
        if (! is_array($valor) || ($valor !== [] && array_is_list($valor))) {
            throw new DomainException("{$campo}: informe um objeto JSON, ex.: {\"chave\": \"valor\"}.");
        }

        return $valor;
    }
}
