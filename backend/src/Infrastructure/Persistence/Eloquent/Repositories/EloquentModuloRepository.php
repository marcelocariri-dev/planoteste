<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Src\Domain\Modulo\Entities\Modulo;
use Src\Domain\Modulo\Repositories\ModuloRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\Models\ModuloModel;

final class EloquentModuloRepository implements ModuloRepositoryInterface
{
    public function buscarPorId(int $id): ?Modulo
    {
        $m = ModuloModel::find($id);

        return $m ? $this->entidade($m) : null;
    }

    public function buscarPorTag(string $tag): ?Modulo
    {
        $m = ModuloModel::where('tag', $tag)->first();

        return $m ? $this->entidade($m) : null;
    }

    public function listar(): array
    {
        return ModuloModel::orderBy('nome')->get()->map(fn ($m) => $this->entidade($m))->all();
    }

    public function contagens(): array
    {
        $regras = DB::table('regras_negocio')->selectRaw('modulo_id, COUNT(*) as total')->groupBy('modulo_id')->pluck('total', 'modulo_id');
        $docs = DB::table('documentos')->whereNotNull('modulo_id')->selectRaw('modulo_id, COUNT(*) as total')->groupBy('modulo_id')->pluck('total', 'modulo_id');

        $resultado = [];
        foreach ($regras->keys()->merge($docs->keys())->unique() as $id) {
            $resultado[(int) $id] = ['regras' => (int) ($regras[$id] ?? 0), 'documentos' => (int) ($docs[$id] ?? 0)];
        }

        return $resultado;
    }

    public function salvar(Modulo $modulo): Modulo
    {
        $m = $modulo->id() ? ModuloModel::findOrFail($modulo->id()) : new ModuloModel();
        $m->fill(['nome' => $modulo->nome(), 'tag' => $modulo->tag(), 'descricao' => $modulo->descricao(), 'cor' => $modulo->cor()])->save();

        return $this->entidade($m->refresh());
    }

    public function excluir(int $id): void
    {
        ModuloModel::whereKey($id)->delete();
    }

    private function entidade(ModuloModel $m): Modulo
    {
        return Modulo::restaurar($m->id, $m->nome, $m->tag, $m->descricao, $m->cor);
    }
}
