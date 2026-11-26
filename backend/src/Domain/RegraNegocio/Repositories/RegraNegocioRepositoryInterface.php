<?php

namespace Src\Domain\RegraNegocio\Repositories;

use Src\Domain\RegraNegocio\Entities\RegraNegocio;

interface RegraNegocioRepositoryInterface
{
    public function buscarPorId(int $id): ?RegraNegocio;

    /** @return RegraNegocio[] */
    public function listar(?int $moduloId = null, ?string $status = null, ?string $tipo = null, ?string $busca = null): array;

    public function contarPorModulo(int $moduloId): int;

    /** Salva e, se o texto mudou, grava um snapshot no histórico de versões. */
    public function salvar(RegraNegocio $regra): RegraNegocio;

    /** @return array<int, array{versao:int, titulo:string, conteudo:string, autor:?string, criado_em:string}> mais recente primeiro */
    public function versoes(int $regraId): array;

    public function excluir(int $id): void;
}
