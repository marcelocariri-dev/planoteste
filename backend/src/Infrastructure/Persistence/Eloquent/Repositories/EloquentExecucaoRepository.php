<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Domain\Execucao\Entities\Execucao;
use Src\Domain\Execucao\Enums\ResultadoExecucao;
use Src\Domain\Execucao\Repositories\ExecucaoRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\Models\ExecucaoModel;

final class EloquentExecucaoRepository implements ExecucaoRepositoryInterface
{
    public function salvar(Execucao $execucao): Execucao
    {
        $model = ExecucaoModel::create([
            'caso_id' => $execucao->casoId(),
            'resultado' => $execucao->resultado()->value,
            'observacao' => $execucao->observacao(),
            'executado_por' => $execucao->executadoPor(),
            'executado_em' => $execucao->executadoEm(),
        ]);

        return $this->paraEntidade($model->refresh());
    }

    public function listarPorCaso(int $casoId): array
    {
        return ExecucaoModel::where('caso_id', $casoId)
            ->orderByDesc('executado_em')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ExecucaoModel $m) => $this->paraEntidade($m))
            ->all();
    }

    public function ultimosResultados(array $casoIds): array
    {
        if ($casoIds === []) {
            return [];
        }

        // última execução = maior id por caso (execuções são só inseridas, nunca editadas)
        $ultimosIds = ExecucaoModel::selectRaw('MAX(id) as id')
            ->whereIn('caso_id', $casoIds)
            ->groupBy('caso_id');

        return ExecucaoModel::whereIn('id', $ultimosIds)
            ->get(['caso_id', 'resultado'])
            ->mapWithKeys(fn (ExecucaoModel $m) => [$m->caso_id => ResultadoExecucao::from($m->resultado)])
            ->all();
    }

    private function paraEntidade(ExecucaoModel $m): Execucao
    {
        return Execucao::restaurar(
            id: $m->id,
            casoId: $m->caso_id,
            resultado: ResultadoExecucao::from($m->resultado),
            observacao: $m->observacao,
            executadoPor: $m->executado_por,
            executadoEm: $m->executado_em,
        );
    }
}
