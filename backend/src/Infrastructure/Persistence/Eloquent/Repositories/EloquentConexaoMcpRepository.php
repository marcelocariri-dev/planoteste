<?php

namespace Src\Infrastructure\Persistence\Eloquent\Repositories;

use Src\Domain\IntegracaoMcp\Entities\ConexaoMcp;
use Src\Domain\IntegracaoMcp\Repositories\ConexaoMcpRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\Models\ConexaoMcpModel;

final class EloquentConexaoMcpRepository implements ConexaoMcpRepositoryInterface
{
    public function buscarPorId(int $id): ?ConexaoMcp
    {
        $m = ConexaoMcpModel::find($id);

        return $m ? $this->entidade($m) : null;
    }

    public function listar(): array
    {
        return ConexaoMcpModel::orderBy('nome')->get()->map(fn ($m) => $this->entidade($m))->all();
    }

    public function salvar(ConexaoMcp $c): ConexaoMcp
    {
        $m = $c->id() ? ConexaoMcpModel::findOrFail($c->id()) : new ConexaoMcpModel();
        $m->fill([
            'nome' => $c->nome(),
            'url' => $c->url(),
            'token' => $c->token(),
            'cabecalhos' => $c->cabecalhos(),
            'ferramenta_casos' => $c->ferramentaCasos(),
            'argumentos_padrao' => $c->argumentosPadrao(),
            'ativo' => $c->ativo(),
            'ultimo_teste_em' => $c->ultimoTesteEm(),
            'ultimo_teste_ok' => $c->ultimoTesteOk(),
            'ultimo_teste_mensagem' => $c->ultimoTesteMensagem(),
        ])->save();

        return $this->entidade($m->refresh());
    }

    public function excluir(int $id): void
    {
        ConexaoMcpModel::whereKey($id)->delete();
    }

    private function entidade(ConexaoMcpModel $m): ConexaoMcp
    {
        return ConexaoMcp::restaurar(
            $m->id, $m->nome, $m->url, $m->token, $m->cabecalhos ?? [], $m->ferramenta_casos, $m->argumentos_padrao ?? [],
            (bool) $m->ativo, $m->ultimo_teste_em, $m->ultimo_teste_ok, $m->ultimo_teste_mensagem,
        );
    }
}
