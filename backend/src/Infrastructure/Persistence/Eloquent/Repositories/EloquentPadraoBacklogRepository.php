<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Domain\Backlog\Entities\PadraoBacklog;
use Src\Domain\Backlog\Repositories\PadraoBacklogRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\Models\PadraoBacklogModel;

final class EloquentPadraoBacklogRepository implements PadraoBacklogRepositoryInterface
{
    public function buscarPorId(int $id): ?PadraoBacklog
    {
        $m = PadraoBacklogModel::find($id);

        return $m ? PadraoBacklog::restaurar($m->id, $m->nome, $m->descricao, $m->instrucoes) : null;
    }

    public function listar(): array
    {
        return PadraoBacklogModel::orderBy('id')->get()
            ->map(fn ($m) => PadraoBacklog::restaurar($m->id, $m->nome, $m->descricao, $m->instrucoes))->all();
    }

    public function salvar(PadraoBacklog $p): PadraoBacklog
    {
        $m = $p->id() ? PadraoBacklogModel::findOrFail($p->id()) : new PadraoBacklogModel();
        $m->fill(['nome' => $p->nome(), 'descricao' => $p->descricao(), 'instrucoes' => $p->instrucoes()])->save();

        return PadraoBacklog::restaurar($m->id, $m->nome, $m->descricao, $m->instrucoes);
    }

    public function excluir(int $id): void
    {
        PadraoBacklogModel::whereKey($id)->delete();
    }
}
