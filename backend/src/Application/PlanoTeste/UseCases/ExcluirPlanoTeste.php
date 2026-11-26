<?php

namespace Src\Application\PlanoTeste\UseCases;

use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

final class ExcluirPlanoTeste
{
    public function __construct(private readonly PlanoTesteRepositoryInterface $planos)
    {
    }

    public function executar(int $id): void
    {
        $this->planos->buscarPorId($id)
            ?? throw EntidadeNaoEncontradaException::para('Plano de teste', $id);

        // casos e execuções são removidos em cascata pela FK
        $this->planos->excluir($id);
    }
}
