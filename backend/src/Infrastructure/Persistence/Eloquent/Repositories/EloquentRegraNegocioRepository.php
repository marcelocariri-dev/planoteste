<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\Domain\RegraNegocio\Entities\RegraNegocio;
use Src\Domain\RegraNegocio\Enums\DecisaoValidacao;
use Src\Domain\RegraNegocio\Enums\PapelValidador;
use Src\Domain\RegraNegocio\Enums\StatusRegra;
use Src\Domain\RegraNegocio\Enums\TipoRegra;
use Src\Domain\RegraNegocio\Repositories\RegraNegocioRepositoryInterface;
use Src\Domain\RegraNegocio\ValueObjects\Validacao;
use Src\Infrastructure\Persistence\Eloquent\Models\RegraNegocioModel;

final class EloquentRegraNegocioRepository implements RegraNegocioRepositoryInterface
{
    private const VERSOES = 'regras_negocio_versoes';

    public function buscarPorId(int $id): ?RegraNegocio
    {
        $m = RegraNegocioModel::find($id);

        return $m ? $this->entidade($m) : null;
    }

    public function listar(?int $moduloId = null, ?string $status = null, ?string $tipo = null, ?string $busca = null): array
    {
        return RegraNegocioModel::query()
            ->when($moduloId, fn ($q) => $q->where('modulo_id', $moduloId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($tipo, fn ($q) => $q->where('tipo', $tipo))
            ->when($busca, fn ($q) => $q->where(fn ($q) => $q->where('titulo', 'like', "%{$busca}%")->orWhere('conteudo', 'like', "%{$busca}%")))
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->get()->map(fn ($m) => $this->entidade($m))->all();
    }

    public function contarPorModulo(int $moduloId): int
    {
        return RegraNegocioModel::where('modulo_id', $moduloId)->count();
    }

    public function salvar(RegraNegocio $regra): RegraNegocio
    {
        return DB::transaction(function () use ($regra) {
            $m = $regra->id() ? RegraNegocioModel::findOrFail($regra->id()) : new RegraNegocioModel();
            $dados = [
                'modulo_id' => $regra->moduloId(),
                'titulo' => $regra->titulo(),
                'tipo' => $regra->tipo()->value,
                'conteudo' => $regra->conteudo(),
                'status' => $regra->status()->value,
                'versao' => $regra->versao(),
                'autor' => $regra->autor(),
            ];
            foreach (PapelValidador::cases() as $papel) {
                $v = $regra->validacao($papel);
                $dados += [
                    "{$papel->value}_decisao" => $v->decisao->value,
                    "{$papel->value}_responsavel" => $v->responsavel,
                    "{$papel->value}_parecer" => $v->parecer,
                    "{$papel->value}_em" => $v->em,
                ];
            }
            $m->fill($dados)->save();

            if ($regra->conteudoFoiAlterado()) {
                DB::table(self::VERSOES)->updateOrInsert(
                    ['regra_id' => $m->id, 'versao' => $regra->versao()],
                    ['titulo' => $regra->titulo(), 'conteudo' => $regra->conteudo(), 'autor' => $regra->autor(), 'criado_em' => now()],
                );
            }

            return $this->entidade($m->refresh());
        });
    }

    public function versoes(int $regraId): array
    {
        return DB::table(self::VERSOES)->where('regra_id', $regraId)->orderByDesc('versao')
            ->get(['versao', 'titulo', 'conteudo', 'autor', 'criado_em'])
            ->map(fn ($v) => (array) $v + ['versao' => (int) $v->versao])->all();
    }

    public function excluir(int $id): void
    {
        RegraNegocioModel::whereKey($id)->delete();
    }

    private function entidade(RegraNegocioModel $m): RegraNegocio
    {
        $validacoes = [];
        foreach (PapelValidador::cases() as $papel) {
            $p = $papel->value;
            $validacoes[$p] = new Validacao(
                $papel,
                DecisaoValidacao::from($m->{"{$p}_decisao"}),
                $m->{"{$p}_responsavel"},
                $m->{"{$p}_parecer"},
                $m->{"{$p}_em"},
            );
        }

        return RegraNegocio::restaurar(
            $m->id, $m->modulo_id, $m->titulo, TipoRegra::from($m->tipo), (string) $m->conteudo, StatusRegra::from($m->status),
            $m->versao, $m->autor, $validacoes,
            $m->updated_at ? DateTimeImmutable::createFromInterface($m->updated_at) : null,
        );
    }
}
