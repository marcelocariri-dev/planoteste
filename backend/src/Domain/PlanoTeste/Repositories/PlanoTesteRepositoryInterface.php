<?php

namespace Src\Domain\PlanoTeste\Repositories;

use Src\Domain\PlanoTeste\Entities\PlanoTeste;

interface PlanoTesteRepositoryInterface
{
    public function buscarPorId(int $id): ?PlanoTeste;

    /** @return PlanoTeste[] */
    public function listar(?string $busca = null, ?string $status = null): array;

    public function salvar(PlanoTeste $plano): PlanoTeste;

    public function excluir(int $id): void;
}
