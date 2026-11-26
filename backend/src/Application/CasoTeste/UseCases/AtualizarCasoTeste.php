<?php

namespace Src\Application\CasoTeste\UseCases;

use Src\Application\CasoTeste\DTOs\CasoTesteDados;
use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

final class AtualizarCasoTeste
{
    public function __construct(
        private readonly PlanoTesteRepositoryInterface $planos,
        private readonly CasoTesteRepositoryInterface $casos,
    ) {
    }

    public function executar(int $casoId, CasoTesteDados $dados): array
    {
        $caso = $this->casos->buscarPorId($casoId)
            ?? throw EntidadeNaoEncontradaException::para('Caso de teste', $casoId);

        $this->planos->buscarPorId($caso->planoId())?->garantirEditavel();

        $caso->atualizar(
            $dados->titulo,
            $dados->passos,
            $dados->resultadoEsperado,
            $dados->prioridade,
            $dados->preCondicoes,
            $dados->ordem ?? $caso->ordem(),
        );

        return $this->casos->salvar($caso)->toArray();
    }
}
