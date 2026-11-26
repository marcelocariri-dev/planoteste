<?php

namespace Src\Application\PlanoTeste\UseCases;

use Src\Application\PlanoTeste\DTOs\PlanoTesteDados;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

final class AtualizarPlanoTeste
{
    public function __construct(private readonly PlanoTesteRepositoryInterface $planos)
    {
    }

    public function executar(int $id, PlanoTesteDados $dados): array
    {
        $plano = $this->planos->buscarPorId($id)
            ?? throw EntidadeNaoEncontradaException::para('Plano de teste', $id);

        $plano->atualizar($dados->nome, $dados->descricao, $dados->projeto, $dados->versao, $dados->responsavel);

        return $this->planos->salvar($plano)->toArray();
    }
}
