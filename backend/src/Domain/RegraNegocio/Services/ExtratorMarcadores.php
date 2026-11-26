<?php

namespace Src\Domain\RegraNegocio\Services;

use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\RegraNegocio\Enums\TipoMarcador;
use Src\Domain\RegraNegocio\ValueObjects\CasoExtraido;
use Src\Domain\RegraNegocio\ValueObjects\Marcador;

/**
 * Lê os blocos marcados no texto da regra e transforma os de tipo "caso" em casos de teste.
 *
 *   ::: caso [alta] Venda com desconto acima do limite
 *   Pré-condições: usuário sem permissão de gerente
 *   Passos:
 *   1. Incluir produto
 *   2. Aplicar 15% de desconto
 *   Resultado esperado: sistema pede senha do gerente
 *   :::
 *
 * Também aceita Gherkin dentro do bloco (Dado / Quando / Então / E).
 */
final class ExtratorMarcadores
{
    private const ABERTURA = '/^\s*:::\s*(caso|regra|criterio|excecao|duvida)\b\s*(?:\[([a-zA-Zçí]+)\])?\s*(.*)$/u';
    private const FECHAMENTO = '/^\s*:::\s*$/';

    /** @return Marcador[] */
    public function extrair(string $conteudo): array
    {
        $linhas = preg_split('/\R/u', $conteudo);
        $marcadores = [];
        $aberto = null;

        foreach ($linhas as $i => $linha) {
            if (preg_match(self::ABERTURA, $linha, $m)) {
                if ($aberto) { // bloco não fechado: fecha antes de abrir o próximo
                    $marcadores[] = $this->criar($aberto);
                }
                $aberto = ['tipo' => $m[1], 'prioridade' => $m[2] ?? '', 'titulo' => trim($m[3] ?? ''), 'linha' => $i + 1, 'corpo' => []];
                continue;
            }

            if ($aberto && preg_match(self::FECHAMENTO, $linha)) {
                $marcadores[] = $this->criar($aberto);
                $aberto = null;
                continue;
            }

            if ($aberto) {
                $aberto['corpo'][] = $linha;
            }
        }

        if ($aberto) {
            $marcadores[] = $this->criar($aberto);
        }

        return $marcadores;
    }

    /** @return CasoExtraido[] */
    public function casos(string $conteudo): array
    {
        $casos = [];
        foreach ($this->extrair($conteudo) as $marcador) {
            if ($marcador->tipo === TipoMarcador::CASO) {
                $casos[] = $this->paraCaso($marcador);
            }
        }

        return $casos;
    }

    public function paraCaso(Marcador $marcador): CasoExtraido
    {
        $secoes = ['pre' => [], 'passos' => [], 'esperado' => []];
        $atual = 'passos';

        foreach (preg_split('/\R/u', $marcador->conteudo) as $linha) {
            $limpa = trim($linha);

            if (preg_match('/^pr[ée][\s-]?condi[çc](?:ões|oes|ão|ao)\s*:\s*(.*)$/iu', $limpa, $m)) {
                [$atual, $resto] = ['pre', $m[1]];
            } elseif (preg_match('/^passos\s*:\s*(.*)$/iu', $limpa, $m)) {
                [$atual, $resto] = ['passos', $m[1]];
            } elseif (preg_match('/^resultados?\s+esperados?\s*:\s*(.*)$/iu', $limpa, $m)) {
                [$atual, $resto] = ['esperado', $m[1]];
            } elseif (preg_match('/^(dado|quando|ent[ãa]o)\b/iu', $limpa, $m)) {
                $atual = match (mb_strtolower($m[1])) {
                    'dado' => 'pre',
                    'quando' => 'passos',
                    default => 'esperado',
                };
                $resto = $limpa;
            } else {
                $resto = $linha;
            }

            $secoes[$atual][] = $resto;
        }

        $texto = fn (array $l) => trim(implode("\n", $l));
        $titulo = $marcador->titulo !== '' ? $marcador->titulo : $this->primeiraLinha($marcador->conteudo, 'Caso sem título');

        return new CasoExtraido(
            titulo: mb_substr($titulo, 0, 200),
            passos: $texto($secoes['passos']) ?: "Executar o cenário: {$titulo}",
            resultadoEsperado: $texto($secoes['esperado']) ?: 'Comportamento conforme descrito na regra de negócio.',
            prioridade: $marcador->prioridade ?? Prioridade::MEDIA,
            preCondicoes: $texto($secoes['pre']) ?: null,
        );
    }

    private function criar(array $aberto): Marcador
    {
        $conteudo = trim(implode("\n", $aberto['corpo']));
        $titulo = $aberto['titulo'] !== '' ? $aberto['titulo'] : $this->primeiraLinha($conteudo, '');

        return new Marcador(
            tipo: TipoMarcador::from($aberto['tipo']),
            titulo: $titulo,
            conteudo: $conteudo,
            linha: $aberto['linha'],
            prioridade: $this->prioridade($aberto['prioridade']),
        );
    }

    private function prioridade(string $valor): ?Prioridade
    {
        $valor = mb_strtolower($valor);

        return match ($valor) {
            'baixa' => Prioridade::BAIXA,
            'media', 'média' => Prioridade::MEDIA,
            'alta' => Prioridade::ALTA,
            'critica', 'crítica' => Prioridade::CRITICA,
            default => null,
        };
    }

    private function primeiraLinha(string $texto, string $padrao): string
    {
        foreach (preg_split('/\R/u', $texto) as $linha) {
            $linha = trim(ltrim(trim($linha), '#*-0123456789. '));
            if ($linha !== '') {
                return mb_substr($linha, 0, 120);
            }
        }

        return $padrao;
    }
}
