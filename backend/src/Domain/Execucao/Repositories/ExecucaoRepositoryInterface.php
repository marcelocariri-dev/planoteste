<?php

namespace Src\Domain\Execucao\Repositories;

use Src\Domain\Execucao\Entities\Execucao;
use Src\Domain\Execucao\Enums\ResultadoExecucao;

interface ExecucaoRepositoryInterface
{
    public function salvar(Execucao $execucao): Execucao;

    /** @return Execucao[] mais recentes primeiro */
    public function listarPorCaso(int $casoId): array;

    /**
     * Resultado da última execução de cada caso informado.
     *
     * @param  int[]  $casoIds
     * @return array<int, ResultadoExecucao> chave = caso_id (casos sem execução não aparecem)
     */
    public function ultimosResultados(array $casoIds): array;
}
