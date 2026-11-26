<?php

namespace Src\Application\CasoTeste\UseCases;

use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

final class RemoverCasoTeste
{
    public function __construct(
        private readonly PlanoTesteRepositoryInterface $planos,
        private readonly CasoTesteRepositoryInterface $casos,
    ) {
    }

    public function executar(int $casoId): void
    {
        $caso = $this->casos->buscarPorId($casoId)
            ?? throw EntidadeNaoEncontradaException::para('Caso de teste', $casoId);

        $this->planos->buscarPorId($caso->planoId())?->garantirEditavel();

        $this->casos->excluir($casoId);
    }
}
