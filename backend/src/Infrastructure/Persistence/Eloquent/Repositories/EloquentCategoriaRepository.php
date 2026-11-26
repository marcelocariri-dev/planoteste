<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Domain\Cofre\Entities\Categoria;
use Src\Domain\Cofre\Repositories\CategoriaRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\Models\CategoriaDocumentoModel;
use Src\Infrastructure\Persistence\Eloquent\Models\DocumentoModel;

final class EloquentCategoriaRepository implements CategoriaRepositoryInterface
{
    public function buscarPorId(int $id): ?Categoria
    {
        $m = CategoriaDocumentoModel::find($id);

        return $m ? Categoria::restaurar($m->id, $m->nome, $m->descricao) : null;
    }

    public function listar(): array
    {
        return CategoriaDocumentoModel::orderBy('nome')->get()
            ->map(fn ($m) => Categoria::restaurar($m->id, $m->nome, $m->descricao))->all();
    }

    public function salvar(Categoria $categoria): Categoria
    {
        $m = $categoria->id() ? CategoriaDocumentoModel::findOrFail($categoria->id()) : new CategoriaDocumentoModel();
        $m->fill(['nome' => $categoria->nome(), 'descricao' => $categoria->descricao()])->save();

        return Categoria::restaurar($m->id, $m->nome, $m->descricao);
    }

    public function contarDocumentos(int $categoriaId): int
    {
        return DocumentoModel::where('categoria_id', $categoriaId)->count();
    }

    public function excluir(int $id): void
    {
        CategoriaDocumentoModel::whereKey($id)->delete();
    }
}
