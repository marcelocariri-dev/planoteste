<?php

namespace Src\Domain\Cofre\Repositories;

use Src\Domain\Cofre\Entities\Categoria;

interface CategoriaRepositoryInterface
{
    public function buscarPorId(int $id): ?Categoria;

    /** @return Categoria[] */
    public function listar(): array;

    public function salvar(Categoria $categoria): Categoria;

    public function contarDocumentos(int $categoriaId): int;

    public function excluir(int $id): void;
}
