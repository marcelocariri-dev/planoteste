<?php

namespace Src\Domain\Backlog\Repositories;

use Src\Domain\Backlog\Entities\PadraoBacklog;

interface PadraoBacklogRepositoryInterface
{
    public function buscarPorId(int $id): ?PadraoBacklog;

    /** @return PadraoBacklog[] */
    public function listar(): array;

    public function salvar(PadraoBacklog $padrao): PadraoBacklog;

    public function excluir(int $id): void;
}
