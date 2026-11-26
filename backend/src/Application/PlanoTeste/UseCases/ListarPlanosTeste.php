<?php

namespace Src\Application\PlanoTeste\UseCases;

use Src\Application\PlanoTeste\Services\ResultadosDoPlano;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;

final class ListarPlanosTeste
{
    public function __construct(
        private readonly PlanoTesteRepositoryInterface $planos,
        private readonly ResultadosDoPlano $resultados,
    ) {
    }

    public function executar(?string $busca = null, ?string $status = null): array
    {
        return array_map(function ($plano) {
            $resumo = $this->resultados->carregar($plano->id())['resumo'];

            return $plano->toArray() + ['resumo' => $resumo->toArray()];
        }, $this->planos->listar($busca, $status));
    }
}
