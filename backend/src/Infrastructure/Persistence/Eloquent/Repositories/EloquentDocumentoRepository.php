<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Src\Domain\Cofre\Entities\Documento;
use Src\Domain\Cofre\Enums\OrigemDocumento;
use Src\Domain\Cofre\Repositories\DocumentoRepositoryInterface;
use Src\Domain\Cofre\ValueObjects\Arquivo;
use Src\Infrastructure\Persistence\Eloquent\Models\DocumentoModel;

final class EloquentDocumentoRepository implements DocumentoRepositoryInterface
{
    public function buscarPorId(int $id): ?Documento
    {
        $m = DocumentoModel::find($id);

        return $m ? $this->entidade($m) : null;
    }

    public function listar(array $filtros = []): array
    {
        $busca = $filtros['busca'] ?? null;

        return DocumentoModel::query()
            ->when($busca, fn ($q) => $q->where(fn ($q) => $q
                ->where('titulo', 'like', "%{$busca}%")
                ->orWhere('descricao', 'like', "%{$busca}%")
                ->orWhere('nome_original', 'like', "%{$busca}%")
                ->orWhere('tags', 'like', '%' . strtoupper($busca) . '%')))
            ->when($filtros['categoria_id'] ?? null, fn ($q, $v) => $q->where('categoria_id', $v))
            ->when($filtros['modulo_id'] ?? null, fn ($q, $v) => $q->where('modulo_id', $v))
            ->when($filtros['regra_id'] ?? null, fn ($q, $v) => $q->where('regra_id', $v))
            ->when($filtros['sem_modulo'] ?? false, fn ($q) => $q->whereNull('modulo_id'))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get()->map(fn ($m) => $this->entidade($m))->all();
    }

    public function buscarVarios(array $ids): array
    {
        return $ids === [] ? [] : DocumentoModel::whereIn('id', $ids)->get()->map(fn ($m) => $this->entidade($m))->all();
    }

    public function salvar(Documento $d): Documento
    {
        $m = $d->id() ? DocumentoModel::findOrFail($d->id()) : new DocumentoModel();
        $m->fill([
            'titulo' => $d->titulo(),
            'descricao' => $d->descricao(),
            'categoria_id' => $d->categoriaId(),
            'modulo_id' => $d->moduloId(),
            'regra_id' => $d->regraId(),
            'origem' => $d->origem()->value,
            'caminho' => $d->arquivo()->caminho,
            'nome_original' => $d->arquivo()->nomeOriginal,
            'mime_type' => $d->arquivo()->mimeType,
            'tamanho' => $d->arquivo()->tamanho,
            'tags' => $d->tags(),
            'enviado_por' => $d->enviadoPor(),
        ])->save();

        return $this->entidade($m->refresh());
    }

    public function excluir(int $id): void
    {
        DocumentoModel::whereKey($id)->delete();
    }

    private function entidade(DocumentoModel $m): Documento
    {
        return Documento::restaurar(
            $m->id, $m->titulo, $m->descricao, $m->categoria_id, $m->modulo_id, $m->regra_id,
            OrigemDocumento::from($m->origem),
            new Arquivo($m->caminho, $m->nome_original, $m->mime_type, $m->tamanho),
            $m->tags ?? [], $m->enviado_por,
            $m->created_at ? DateTimeImmutable::createFromInterface($m->created_at) : null,
        );
    }
}
