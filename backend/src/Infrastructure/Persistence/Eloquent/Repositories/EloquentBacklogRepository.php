<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Src\Domain\Backlog\Entities\Backlog;
use Src\Domain\Backlog\Enums\StatusBacklog;
use Src\Domain\Backlog\Repositories\BacklogRepositoryInterface;
use Src\Domain\Backlog\ValueObjects\Epico;
use Src\Domain\Backlog\ValueObjects\Historia;
use Src\Infrastructure\Persistence\Eloquent\Models\BacklogModel;

final class EloquentBacklogRepository implements BacklogRepositoryInterface
{
    public function buscarPorId(int $id): ?Backlog
    {
        $m = BacklogModel::find($id);

        return $m ? $this->entidade($m) : null;
    }

    public function listar(?int $moduloId = null): array
    {
        return BacklogModel::query()
            ->when($moduloId, fn ($q) => $q->where('modulo_id', $moduloId))
            ->orderByDesc('id')->get()->map(fn ($m) => $this->entidade($m))->all();
    }

    public function salvar(Backlog $b): Backlog
    {
        $m = $b->id() ? BacklogModel::findOrFail($b->id()) : new BacklogModel();
        $m->fill([
            'titulo' => $b->titulo(),
            'modulo_id' => $b->moduloId(),
            'padrao_id' => $b->padraoId(),
            'orientacoes' => $b->orientacoes(),
            'documento_ids' => $b->documentoIds(),
            'regra_ids' => $b->regraIds(),
            'prompt' => $b->prompt(),
            'status' => $b->status()->value,
            'epicos' => array_map(fn (Epico $e) => $e->toArray(), $b->epicos()),
            'resposta_bruta' => $b->respostaBruta(),
            'modelo' => $b->modelo(),
            'erro' => $b->erro(),
        ])->save();

        return $this->entidade($m->refresh());
    }

    public function excluir(int $id): void
    {
        BacklogModel::whereKey($id)->delete();
    }

    private function entidade(BacklogModel $m): Backlog
    {
        $epicos = array_map(fn (array $e) => new Epico(
            $e['codigo'], $e['titulo'], $e['descricao'] ?? null,
            array_map(fn (array $h) => new Historia(
                $h['codigo'], $h['titulo'], $h['como_um'] ?? '', $h['quero'] ?? '', $h['para'] ?? '',
                $h['criterios_aceite'] ?? [], $h['prioridade'] ?? 'Should', $h['estimativa'] ?? null, $h['observacoes'] ?? null,
            ), $e['historias'] ?? []),
        ), $m->epicos ?? []);

        return Backlog::restaurar(
            $m->id, $m->titulo, $m->modulo_id, $m->padrao_id, $m->orientacoes, $m->documento_ids ?? [], $m->regra_ids ?? [],
            $m->prompt, StatusBacklog::from($m->status), $epicos, $m->resposta_bruta, $m->modelo, $m->erro,
            $m->created_at ? DateTimeImmutable::createFromInterface($m->created_at) : null,
        );
    }
}
