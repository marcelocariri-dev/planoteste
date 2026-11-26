<?php

namespace Src\Domain\Modulo\Repositories;

use Src\Domain\Modulo\Entities\Modulo;

interface ModuloRepositoryInterface
{
    public function buscarPorId(int $id): ?Modulo;

    public function buscarPorTag(string $tag): ?Modulo;

    /** @return Modulo[] */
    public function listar(): array;

    /** @return array<int, array{regras: int, documentos: int}> chave = modulo_id */
    public function contagens(): array;

    public function salvar(Modulo $modulo): Modulo;

    public function excluir(int $id): void;
}
