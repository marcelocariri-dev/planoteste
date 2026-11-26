<?php

namespace Src\Domain\Modulo\Entities;

use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Support\Texto;

/**
 * Módulo/departamento do sistema. A tag é o identificador usado para departamentalizar
 * regras de negócio e documentos do cofre.
 */
final class Modulo
{
    private function __construct(
        private ?int $id,
        private string $nome,
        private string $tag,
        private ?string $descricao,
        private string $cor,
    ) {
        $this->validar();
    }

    public static function criar(string $nome, ?string $tag = null, ?string $descricao = null, ?string $cor = null): self
    {
        $nome = trim($nome);

        return new self(null, $nome, Texto::tag($tag ?: $nome), Texto::vazioParaNull($descricao), $cor ?: '#2563eb');
    }

    public static function restaurar(int $id, string $nome, string $tag, ?string $descricao, string $cor): self
    {
        return new self($id, $nome, $tag, $descricao, $cor);
    }

    public function atualizar(string $nome, ?string $tag, ?string $descricao, ?string $cor): void
    {
        $this->nome = trim($nome);
        $this->tag = Texto::tag($tag ?: $this->nome);
        $this->descricao = Texto::vazioParaNull($descricao);
        $this->cor = $cor ?: $this->cor;
        $this->validar();
    }

    private function validar(): void
    {
        if ($this->nome === '' || mb_strlen($this->nome) > 100) {
            throw new DomainException('O nome do módulo é obrigatório (máx. 100 caracteres).');
        }
        if ($this->tag === '' || strlen($this->tag) > 40) {
            throw new DomainException('A tag do módulo é obrigatória (máx. 40 caracteres).');
        }
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $this->cor)) {
            throw new DomainException('Cor inválida. Use o formato #RRGGBB.');
        }
    }

    public function definirId(int $id): void
    {
        $this->id ??= $id;
    }

    public function id(): ?int { return $this->id; }
    public function nome(): string { return $this->nome; }
    public function tag(): string { return $this->tag; }
    public function descricao(): ?string { return $this->descricao; }
    public function cor(): string { return $this->cor; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'tag' => $this->tag,
            'descricao' => $this->descricao,
            'cor' => $this->cor,
        ];
    }
}
