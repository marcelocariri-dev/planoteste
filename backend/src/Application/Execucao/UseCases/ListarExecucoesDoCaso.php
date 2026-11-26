<?php

namespace Src\Application\Execucao\UseCases;

use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\Execucao\Repositories\ExecucaoRepositoryInterface;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

final class ListarExecucoesDoCaso
{
    public function __construct(
        private readonly CasoTesteRepositoryInterface $casos,
        private readonly ExecucaoRepositoryInterface $execucoes,
    ) {
    }

    public function executar(int $casoId): array
    {
        $this->casos->buscarPorId($casoId)
            ?? throw EntidadeNaoEncontradaException::para('Caso de teste', $casoId);

        return array_map(fn ($e) => $e->toArray(), $this->execucoes->listarPorCaso($casoId));
    }
}
