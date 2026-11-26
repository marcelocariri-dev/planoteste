<?php

namespace Src\Domain\Backlog\Services;

use Src\Domain\Backlog\ValueObjects\Epico;
use Src\Domain\Backlog\ValueObjects\Historia;
use Src\Domain\Shared\Exceptions\DomainException;

/**
 * Converte o texto devolvido pela IA (ou colado pelo usuário) em épicos e histórias.
 * Tolera o JSON dentro de bloco ```json e pequenas variações de nomes de campo.
 */
final class InterpretadorRespostaBacklog
{
    /** @return Epico[] */
    public function interpretar(string $resposta): array
    {
        $dados = $this->extrairJson($resposta);
        $listaEpicos = $dados['epicos'] ?? $dados['epics'] ?? (array_is_list($dados) ? $dados : null);

        if (! is_array($listaEpicos) || $listaEpicos === []) {
            throw new DomainException('A resposta não contém a lista "epicos".');
        }

        $epicos = [];
        $nHistoria = 0;
        foreach (array_values($listaEpicos) as $i => $e) {
            if (! is_array($e)) {
                continue;
            }
            $historias = [];
            foreach ((array) ($e['historias'] ?? $e['stories'] ?? $e['user_stories'] ?? []) as $h) {
                if (! is_array($h)) {
                    continue;
                }
                $nHistoria++;
                $historias[] = new Historia(
                    codigo: $this->texto($h, ['codigo', 'id', 'code']) ?: sprintf('US-%02d', $nHistoria),
                    titulo: $this->texto($h, ['titulo', 'title', 'nome']) ?: "História {$nHistoria}",
                    comoUm: $this->texto($h, ['como_um', 'como', 'persona', 'as_a']),
                    quero: $this->texto($h, ['quero', 'eu_quero', 'i_want', 'acao']),
                    para: $this->texto($h, ['para', 'para_que', 'so_that', 'beneficio']),
                    criteriosAceite: $this->lista($h['criterios_aceite'] ?? $h['criterios'] ?? $h['acceptance_criteria'] ?? []),
                    prioridade: $this->texto($h, ['prioridade', 'priority']) ?: 'Should',
                    estimativa: is_numeric($h['estimativa'] ?? $h['story_points'] ?? null) ? (int) ($h['estimativa'] ?? $h['story_points']) : null,
                    observacoes: $this->texto($h, ['observacoes', 'notas', 'notes']) ?: null,
                );
            }

            $epicos[] = new Epico(
                codigo: $this->texto($e, ['codigo', 'id', 'code']) ?: sprintf('EP-%02d', $i + 1),
                titulo: $this->texto($e, ['titulo', 'title', 'nome']) ?: 'Épico ' . ($i + 1),
                descricao: $this->texto($e, ['descricao', 'description']) ?: null,
                historias: $historias,
            );
        }

        if ($epicos === []) {
            throw new DomainException('Nenhum épico válido encontrado na resposta.');
        }

        return $epicos;
    }

    private function extrairJson(string $texto): array
    {
        $texto = trim($texto);
        if (preg_match('/```(?:json)?\s*(.+?)```/su', $texto, $m)) {
            $texto = trim($m[1]);
        }

        $dados = json_decode($texto, true);

        if (! is_array($dados)) { // tenta o trecho entre o primeiro { e o último }
            $ini = strpos($texto, '{');
            $fim = strrpos($texto, '}');
            if ($ini !== false && $fim > $ini) {
                $dados = json_decode(substr($texto, $ini, $fim - $ini + 1), true);
            }
        }

        if (! is_array($dados)) {
            throw new DomainException('A resposta não é um JSON válido no formato do padrão.');
        }

        return $dados;
    }

    private function texto(array $dados, array $chaves): string
    {
        foreach ($chaves as $c) {
            if (isset($dados[$c]) && is_scalar($dados[$c])) {
                return trim((string) $dados[$c]);
            }
        }

        return '';
    }

    /** @return string[] */
    private function lista(mixed $valor): array
    {
        if (is_string($valor)) {
            $valor = preg_split('/\R/u', $valor);
        }

        return array_values(array_filter(array_map(
            fn ($v) => is_scalar($v) ? trim((string) $v) : trim(json_encode($v, JSON_UNESCAPED_UNICODE)),
            (array) $valor,
        )));
    }
}
