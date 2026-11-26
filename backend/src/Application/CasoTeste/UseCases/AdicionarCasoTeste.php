<?php

namespace Src\Application\CasoTeste\UseCases;

use Src\Application\CasoTeste\DTOs\CasoTesteDados;
use Src\Domain\CasoTeste\Entities\CasoTeste;
use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

final class AdicionarCasoTeste
{
    public function __construct(
        private readonly PlanoTesteRepositoryInterface $planos,
        private readonly CasoTesteRepositoryInterface $casos,
    ) {
    }

    public function executar(int $planoId, CasoTesteDados $dados): array
    {
        $plano = $this->planos->buscarPorId($planoId)
            ?? throw EntidadeNaoEncontradaException::para('Plano de teste', $planoId);

        $plano->garantirEditavel();

        $caso = CasoTeste::criar(
            planoId: $planoId,
            titulo: $dados->titulo,
            passos: $dados->passos,
            resultadoEsperado: $dados->resultadoEsperado,
            prioridade: $dados->prioridade,
            preCondicoes: $dados->preCondicoes,
            ordem: $dados->ordem ?? $this->casos->proximaOrdem($planoId),
        );

        return $this->casos->salvar($caso)->toArray();
    }
}
