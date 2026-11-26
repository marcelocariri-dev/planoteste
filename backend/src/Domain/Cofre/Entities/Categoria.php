<?php

namespace Src\Domain\Cofre\Entities;

use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Support\Texto;

final class Categoria
{
    private function __construct(private ?int $id, private string $nome, private ?string $descricao)
    {
        if ($this->nome === '' || mb_strlen($this->nome) > 80) {
            throw new DomainException('O nome da categoria é obrigatório (máx. 80 caracteres).');
        }
    }

    public static function criar(string $nome, ?string $descricao = null): self
    {
        return new self(null, trim($nome), Texto::vazioParaNull($descricao));
    }

    public static function restaurar(int $id, string $nome, ?string $descricao): self
    {
        return new self($id, $nome, $descricao);
    }

    public function definirId(int $id): void { $this->id ??= $id; }
    public function id(): ?int { return $this->id; }
    public function nome(): string { return $this->nome; }
    public function descricao(): ?string { return $this->descricao; }

    public function toArray(): array
    {
        return ['id' => $this->id, 'nome' => $this->nome, 'descricao' => $this->descricao];
    }
}
