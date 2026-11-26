<?php

namespace Src\Domain\IntegracaoMcp\Services;

use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\IntegracaoMcp\ValueObjects\ResultadoFerramenta;
use Src\Domain\RegraNegocio\ValueObjects\CasoExtraido;
use Src\Domain\Shared\Exceptions\DomainException;

/**
 * Converte o retorno de uma ferramenta MCP em casos de teste candidatos.
 * Aceita nomes de campo em português ou inglês e listas em chaves comuns (casos, cases, items, data...).
 */
final class MapeadorCasosExternos
{
    private const CHAVES_LISTA = ['casos', 'casos_teste', 'test_cases', 'testCases', 'cases', 'items', 'data', 'results', 'resultados'];

    private const CAMPOS = [
        'id' => ['id', 'codigo', 'code', 'key', 'chave', 'id_externo'],
        'titulo' => ['titulo', 'title', 'nome', 'name', 'summary', 'resumo', 'descricao', 'description'],
        'pre' => ['pre_condicoes', 'precondicoes', 'preconditions', 'precondition', 'pre_condicao', 'preConditions'],
        'passos' => ['passos', 'steps', 'etapas', 'procedimento', 'acoes', 'actions'],
        'esperado' => ['resultado_esperado', 'resultadoEsperado', 'expected_result', 'expectedResult', 'expected', 'esperado'],
        'prioridade' => ['prioridade', 'priority', 'severidade', 'severity'],
    ];

    /** @return CasoExtraido[] */
    public function mapear(ResultadoFerramenta $resultado): array
    {
        if ($resultado->erro) {
            throw new DomainException('O servidor MCP retornou erro: ' . mb_substr(implode(' ', $resultado->textos), 0, 300));
        }

        $itens = $this->localizarLista($resultado->conteudoEstruturado);

        if ($itens === null) {
            foreach ($resultado->textos as $texto) {
                $json = json_decode(trim($texto), true);
                if (is_array($json) && ($itens = $this->localizarLista($json)) !== null) {
                    break;
                }
            }
        }

        if ($itens === null) {
            throw new DomainException('Não encontrei uma lista de casos no retorno da ferramenta. Verifique a ferramenta e os argumentos.');
        }

        $casos = [];
        foreach ($itens as $i => $item) {
            if (is_array($item)) {
                $casos[] = $this->paraCaso($item, $i + 1);
            }
        }

        return $casos;
    }

    private function localizarLista(?array $dados): ?array
    {
        if ($dados === null) {
            return null;
        }
        if (array_is_list($dados)) {
            return $dados;
        }
        foreach (self::CHAVES_LISTA as $chave) {
            if (isset($dados[$chave]) && is_array($dados[$chave])) {
                return $this->localizarLista($dados[$chave]);
            }
        }
        // objeto único com cara de caso
        if ($this->valor($dados, 'titulo') !== null) {
            return [$dados];
        }

        return null;
    }

    private function paraCaso(array $item, int $posicao): CasoExtraido
    {
        $titulo = $this->texto($this->valor($item, 'titulo')) ?: "Caso externo {$posicao}";

        return new CasoExtraido(
            titulo: mb_substr($titulo, 0, 200),
            passos: $this->texto($this->valor($item, 'passos')) ?: "Executar o cenário: {$titulo}",
            resultadoEsperado: $this->texto($this->valor($item, 'esperado')) ?: 'Conforme especificação do caso oficial.',
            prioridade: $this->prioridade($this->texto($this->valor($item, 'prioridade'))),
            preCondicoes: $this->texto($this->valor($item, 'pre')) ?: null,
            idExterno: $this->texto($this->valor($item, 'id')) ?: null,
        );
    }

    private function valor(array $item, string $campo): mixed
    {
        foreach (self::CAMPOS[$campo] as $chave) {
            if (array_key_exists($chave, $item) && $item[$chave] !== null && $item[$chave] !== '') {
                return $item[$chave];
            }
        }

        return null;
    }

    private function texto(mixed $valor): string
    {
        if ($valor === null) {
            return '';
        }
        if (is_scalar($valor)) {
            return trim((string) $valor);
        }
        if (is_array($valor) && array_is_list($valor)) { // lista de passos
            $linhas = [];
            foreach ($valor as $i => $v) {
                $txt = is_array($v)
                    ? trim(($v['acao'] ?? $v['action'] ?? $v['step'] ?? $v['descricao'] ?? json_encode($v, JSON_UNESCAPED_UNICODE))
                        . (isset($v['esperado']) || isset($v['expected']) ? ' → ' . ($v['esperado'] ?? $v['expected']) : ''))
                    : trim((string) $v);
                $linhas[] = preg_match('/^\d+[.)]/', $txt) ? $txt : ($i + 1) . '. ' . $txt;
            }

            return implode("\n", $linhas);
        }

        return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function prioridade(string $valor): Prioridade
    {
        $v = mb_strtolower($valor);

        return match (true) {
            in_array($v, ['critica', 'crítica', 'critical', 'blocker', 'highest', 'p0', '1'], true) => Prioridade::CRITICA,
            in_array($v, ['alta', 'high', 'major', 'p1', '2'], true) => Prioridade::ALTA,
            in_array($v, ['baixa', 'low', 'minor', 'trivial', 'lowest', 'p3', '4'], true) => Prioridade::BAIXA,
            default => Prioridade::MEDIA,
        };
    }
}
