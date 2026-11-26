<?php

namespace Src\Application\PlanoTeste\Services;

use Src\Domain\CasoTeste\Entities\CasoTeste;
use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\Execucao\Repositories\ExecucaoRepositoryInterface;
use Src\Domain\PlanoTeste\ValueObjects\ResumoExecucao;

/**
 * Carrega os casos de um plano já com o resultado da última execução e monta o resumo.
 */
final class ResultadosDoPlano
{
    public function __construct(
        private readonly CasoTesteRepositoryInterface $casos,
        private readonly ExecucaoRepositoryInterface $execucoes,
    ) {
    }

    /** @return array{casos: CasoTeste[], resumo: ResumoExecucao} */
    public function carregar(int $planoId): array
    {
        $casos = $this->casos->listarPorPlano($planoId);
        $ultimos = $this->execucoes->ultimosResultados(array_map(fn (CasoTeste $c) => $c->id(), $casos));

        foreach ($casos as $caso) {
            $caso->definirResultadoAtual($ultimos[$caso->id()] ?? null);
        }

        $resumo = ResumoExecucao::apartirDe(array_map(fn (CasoTeste $c) => $c->resultadoAtual(), $casos));

        return ['casos' => $casos, 'resumo' => $resumo];
    }
}
