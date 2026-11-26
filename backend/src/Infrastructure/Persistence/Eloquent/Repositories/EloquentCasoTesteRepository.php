<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Domain\CasoTeste\Entities\CasoTeste;
use Src\Domain\CasoTeste\Enums\OrigemCaso;
use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\Models\CasoTesteModel;

final class EloquentCasoTesteRepository implements CasoTesteRepositoryInterface
{
    public function buscarPorId(int $id): ?CasoTeste
    {
        $model = CasoTesteModel::find($id);

        return $model ? $this->paraEntidade($model) : null;
    }

    public function listarPorPlano(int $planoId): array
    {
        return CasoTesteModel::where('plano_id', $planoId)
            ->orderBy('ordem')
            ->orderBy('id')
            ->get()
            ->map(fn (CasoTesteModel $m) => $this->paraEntidade($m))
            ->all();
    }

    public function proximaOrdem(int $planoId): int
    {
        return ((int) CasoTesteModel::where('plano_id', $planoId)->max('ordem')) + 1;
    }

    public function referenciasDoPlano(int $planoId): array
    {
        return CasoTesteModel::where('plano_id', $planoId)->whereNotNull('referencia')->pluck('referencia')->all();
    }

    public function salvar(CasoTeste $caso): CasoTeste
    {
        $model = $caso->id() ? CasoTesteModel::findOrFail($caso->id()) : new CasoTesteModel();

        $model->fill([
            'plano_id' => $caso->planoId(),
            'titulo' => $caso->titulo(),
            'pre_condicoes' => $caso->preCondicoes(),
            'passos' => $caso->passos(),
            'resultado_esperado' => $caso->resultadoEsperado(),
            'prioridade' => $caso->prioridade()->value,
            'ordem' => $caso->ordem(),
            'origem' => $caso->origem()->value,
            'referencia' => $caso->referencia(),
        ])->save();

        return $this->paraEntidade($model->refresh());
    }

    public function excluir(int $id): void
    {
        CasoTesteModel::whereKey($id)->delete();
    }

    private function paraEntidade(CasoTesteModel $m): CasoTeste
    {
        return CasoTeste::restaurar(
            id: $m->id,
            planoId: $m->plano_id,
            titulo: $m->titulo,
            preCondicoes: $m->pre_condicoes,
            passos: $m->passos,
            resultadoEsperado: $m->resultado_esperado,
            prioridade: Prioridade::from($m->prioridade),
            ordem: (int) $m->ordem,
            origem: OrigemCaso::tryFrom((string) $m->origem) ?? OrigemCaso::MANUAL,
            referencia: $m->referencia,
        );
    }
}
