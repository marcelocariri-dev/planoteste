<?php

namespace Src\Domain\Backlog\Entities;

use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Support\Texto;

/**
 * Padrão (template de prompt) usado para gerar o product backlog.
 * Variáveis disponíveis: {{modulo}}, {{orientacoes}}, {{regras}}, {{documentos}}.
 */
final class PadraoBacklog
{
    public const VARIAVEIS = ['{{modulo}}', '{{orientacoes}}', '{{regras}}', '{{documentos}}'];

    private function __construct(
        private ?int $id,
        private string $nome,
        private ?string $descricao,
        private string $instrucoes,
    ) {
        if ($this->nome === '' || mb_strlen($this->nome) > 100) {
            throw new DomainException('O nome do padrão é obrigatório (máx. 100 caracteres).');
        }
        if (mb_strlen(trim($this->instrucoes)) < 20) {
            throw new DomainException('Escreva as instruções do padrão (mínimo 20 caracteres).');
        }
        if (! str_contains($this->instrucoes, '{{orientacoes}}')) {
            throw new DomainException('O padrão precisa conter a variável {{orientacoes}}.');
        }
    }

    public static function criar(string $nome, string $instrucoes, ?string $descricao = null): self
    {
        return new self(null, trim($nome), Texto::vazioParaNull($descricao), $instrucoes);
    }

    public static function restaurar(int $id, string $nome, ?string $descricao, string $instrucoes): self
    {
        return new self($id, $nome, $descricao, $instrucoes);
    }

    public function atualizar(string $nome, string $instrucoes, ?string $descricao): void
    {
        $novo = new self($this->id, trim($nome), Texto::vazioParaNull($descricao), $instrucoes);
        [$this->nome, $this->descricao, $this->instrucoes] = [$novo->nome, $novo->descricao, $novo->instrucoes];
    }

    public function definirId(int $id): void { $this->id ??= $id; }
    public function id(): ?int { return $this->id; }
    public function nome(): string { return $this->nome; }
    public function descricao(): ?string { return $this->descricao; }
    public function instrucoes(): string { return $this->instrucoes; }

    public function toArray(): array
    {
        return ['id' => $this->id, 'nome' => $this->nome, 'descricao' => $this->descricao, 'instrucoes' => $this->instrucoes];
    }
}
