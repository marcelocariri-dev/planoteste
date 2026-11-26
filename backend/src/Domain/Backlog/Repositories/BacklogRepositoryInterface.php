<?php

namespace Src\Domain\Backlog\Repositories;

use Src\Domain\Backlog\Entities\Backlog;

interface BacklogRepositoryInterface
{
    public function buscarPorId(int $id): ?Backlog;

    /** @return Backlog[] */
    public function listar(?int $moduloId = null): array;

    public function salvar(Backlog $backlog): Backlog;

    public function excluir(int $id): void;
}
