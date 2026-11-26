<?php

namespace Src\Domain\Cofre\Repositories;

use Src\Domain\Cofre\Entities\Documento;

interface DocumentoRepositoryInterface
{
    public function buscarPorId(int $id): ?Documento;

    /**
     * @param array{busca?: ?string, categoria_id?: ?int, modulo_id?: ?int, regra_id?: ?int, sem_modulo?: bool} $filtros
     * @return Documento[]
     */
    public function listar(array $filtros = []): array;

    /** @param int[] $ids @return Documento[] */
    public function buscarVarios(array $ids): array;

    public function salvar(Documento $documento): Documento;

    public function excluir(int $id): void;
}
