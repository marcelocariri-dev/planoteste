<?php

namespace Src\Application\PlanoTeste\UseCases;

use Src\Application\PlanoTeste\Services\ResultadosDoPlano;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

final class ObterPlanoTeste
{
    public function __construct(
        private readonly PlanoTesteRepositoryInterface $planos,
        private readonly ResultadosDoPlano $resultados,
    ) {
    }

    public function executar(int $id): array
    {
        $plano = $this->planos->buscarPorId($id)
            ?? throw EntidadeNaoEncontradaException::para('Plano de teste', $id);

        ['casos' => $casos, 'resumo' => $resumo] = $this->resultados->carregar($id);

        return $plano->toArray() + [
            'resumo' => $resumo->toArray(),
            'casos' => array_map(fn ($c) => $c->toArray(), $casos),
        ];
    }
}
