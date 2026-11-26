<?php

namespace Src\Domain\CasoTeste\Repositories;

use Src\Domain\CasoTeste\Entities\CasoTeste;

interface CasoTesteRepositoryInterface
{
    public function buscarPorId(int $id): ?CasoTeste;

    /** @return CasoTeste[] ordenados por ordem/id */
    public function listarPorPlano(int $planoId): array;

    public function proximaOrdem(int $planoId): int;

    public function salvar(CasoTeste $caso): CasoTeste;

    public function excluir(int $id): void;
}
