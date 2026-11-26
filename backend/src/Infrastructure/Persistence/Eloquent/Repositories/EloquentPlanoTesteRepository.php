<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Src\Domain\PlanoTeste\Entities\PlanoTeste;
use Src\Domain\PlanoTeste\Enums\StatusPlano;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\Models\PlanoTesteModel;

final class EloquentPlanoTesteRepository implements PlanoTesteRepositoryInterface
{
    public function buscarPorId(int $id): ?PlanoTeste
    {
        $model = PlanoTesteModel::find($id);

        return $model ? $this->paraEntidade($model) : null;
    }

    public function listar(?string $busca = null, ?string $status = null): array
    {
        return PlanoTesteModel::query()
            ->when($busca, function ($q) use ($busca) {
                $q->where(function ($q) use ($busca) {
                    $q->where('nome', 'like', "%{$busca}%")
                        ->orWhere('projeto', 'like', "%{$busca}%")
                        ->orWhere('versao', 'like', "%{$busca}%");
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PlanoTesteModel $m) => $this->paraEntidade($m))
            ->all();
    }

    public function salvar(PlanoTeste $plano): PlanoTeste
    {
        $model = $plano->id() ? PlanoTesteModel::findOrFail($plano->id()) : new PlanoTesteModel();

        $model->fill([
            'nome' => $plano->nome(),
            'descricao' => $plano->descricao(),
            'projeto' => $plano->projeto(),
            'versao' => $plano->versao(),
            'responsavel' => $plano->responsavel(),
            'status' => $plano->status()->value,
        ])->save();

        return $this->paraEntidade($model->refresh());
    }

    public function excluir(int $id): void
    {
        PlanoTesteModel::whereKey($id)->delete();
    }

    private function paraEntidade(PlanoTesteModel $m): PlanoTeste
    {
        return PlanoTeste::restaurar(
            id: $m->id,
            nome: $m->nome,
            descricao: $m->descricao,
            projeto: $m->projeto,
            versao: $m->versao,
            responsavel: $m->responsavel,
            status: StatusPlano::from($m->status),
            criadoEm: $m->created_at ? DateTimeImmutable::createFromInterface($m->created_at) : null,
            atualizadoEm: $m->updated_at ? DateTimeImmutable::createFromInterface($m->updated_at) : null,
        );
    }
}
