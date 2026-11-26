<?php

namespace Src\Domain\PlanoTeste\ValueObjects;

use Src\Domain\Execucao\Enums\ResultadoExecucao;

/**
 * Consolida o resultado atual (última execução) de cada caso de um plano.
 */
final class ResumoExecucao
{
    private function __construct(
        public readonly int $total,
        public readonly int $passou,
        public readonly int $falhou,
        public readonly int $bloqueado,
        public readonly int $naoExecutado,
    ) {
    }

    /**
     * @param  array<int, ResultadoExecucao|null>  $resultadosAtuais  um item por caso; null = nunca executado
     */
    public static function apartirDe(array $resultadosAtuais): self
    {
        $contagem = ['passou' => 0, 'falhou' => 0, 'bloqueado' => 0, 'nao_executado' => 0];

        foreach ($resultadosAtuais as $resultado) {
            $chave = $resultado?->value ?? 'nao_executado';
            $contagem[$chave]++;
        }

        return new self(
            count($resultadosAtuais),
            $contagem['passou'],
            $contagem['falhou'],
            $contagem['bloqueado'],
            $contagem['nao_executado'],
        );
    }

    public function executados(): int
    {
        return $this->total - $this->naoExecutado;
    }

    public function percentualExecutado(): float
    {
        return $this->total === 0 ? 0.0 : round($this->executados() / $this->total * 100, 1);
    }

    public function percentualSucesso(): float
    {
        return $this->executados() === 0 ? 0.0 : round($this->passou / $this->executados() * 100, 1);
    }

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'passou' => $this->passou,
            'falhou' => $this->falhou,
            'bloqueado' => $this->bloqueado,
            'nao_executado' => $this->naoExecutado,
            'percentual_executado' => $this->percentualExecutado(),
            'percentual_sucesso' => $this->percentualSucesso(),
        ];
    }
}
