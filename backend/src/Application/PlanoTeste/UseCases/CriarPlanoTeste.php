<?php

namespace Src\Application\PlanoTeste\UseCases;

use Src\Application\PlanoTeste\DTOs\PlanoTesteDados;
use Src\Domain\PlanoTeste\Entities\PlanoTeste;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;

final class CriarPlanoTeste
{
    public function __construct(private readonly PlanoTesteRepositoryInterface $planos)
    {
    }

    public function executar(PlanoTesteDados $dados): array
    {
        $plano = PlanoTeste::criar($dados->nome, $dados->descricao, $dados->projeto, $dados->versao, $dados->responsavel);

        return $this->planos->salvar($plano)->toArray();
    }
}
