<?php

namespace Src\Domain\PlanoTeste\Entities;

use DateTimeImmutable;
use Src\Domain\PlanoTeste\Enums\StatusPlano;
use Src\Domain\Shared\Exceptions\DomainException;

/**
 * Agregado raiz: um plano de teste de um sistema/módulo em uma versão.
 */
final class PlanoTeste
{
    private function __construct(
        private ?int $id,
        private string $nome,
        private ?string $descricao,
        private ?string $projeto,
        private ?string $versao,
        private ?string $responsavel,
        private StatusPlano $status,
        private ?DateTimeImmutable $criadoEm = null,
        private ?DateTimeImmutable $atualizadoEm = null,
    ) {
        $this->validar();
    }

    public static function criar(
        string $nome,
        ?string $descricao = null,
        ?string $projeto = null,
        ?string $versao = null,
        ?string $responsavel = null,
    ): self {
        return new self(null, trim($nome), $descricao, $projeto, $versao, $responsavel, StatusPlano::RASCUNHO);
    }

    /** Reconstrói a entidade a partir da persistência (sem regras de criação). */
    public static function restaurar(
        int $id,
        string $nome,
        ?string $descricao,
        ?string $projeto,
        ?string $versao,
        ?string $responsavel,
        StatusPlano $status,
        ?DateTimeImmutable $criadoEm,
        ?DateTimeImmutable $atualizadoEm,
    ): self {
        return new self($id, $nome, $descricao, $projeto, $versao, $responsavel, $status, $criadoEm, $atualizadoEm);
    }

    public function atualizar(
        string $nome,
        ?string $descricao,
        ?string $projeto,
        ?string $versao,
        ?string $responsavel,
    ): void {
        $this->garantirEditavel();

        $this->nome = trim($nome);
        $this->descricao = $descricao;
        $this->projeto = $projeto;
        $this->versao = $versao;
        $this->responsavel = $responsavel;
        $this->validar();
    }

    /** Chamado quando a primeira execução é registrada. */
    public function iniciarExecucao(): void
    {
        $this->garantirEditavel();

        if ($this->status === StatusPlano::RASCUNHO) {
            $this->status = StatusPlano::EM_EXECUCAO;
        }
    }

    public function concluir(): void
    {
        if ($this->status === StatusPlano::CONCLUIDO) {
            throw new DomainException('O plano já está concluído.');
        }

        $this->status = StatusPlano::CONCLUIDO;
    }

    public function reabrir(): void
    {
        if ($this->status !== StatusPlano::CONCLUIDO) {
            throw new DomainException('Apenas planos concluídos podem ser reabertos.');
        }

        $this->status = StatusPlano::EM_EXECUCAO;
    }

    public function garantirEditavel(): void
    {
        if (! $this->status->permiteEdicao()) {
            throw new DomainException('Plano concluído não pode ser alterado. Reabra o plano primeiro.');
        }
    }

    private function validar(): void
    {
        if ($this->nome === '') {
            throw new DomainException('O nome do plano é obrigatório.');
        }

        if (mb_strlen($this->nome) > 150) {
            throw new DomainException('O nome do plano deve ter no máximo 150 caracteres.');
        }
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new DomainException('O plano já possui identificador.');
        }

        $this->id = $id;
    }

    public function id(): ?int { return $this->id; }
    public function nome(): string { return $this->nome; }
    public function descricao(): ?string { return $this->descricao; }
    public function projeto(): ?string { return $this->projeto; }
    public function versao(): ?string { return $this->versao; }
    public function responsavel(): ?string { return $this->responsavel; }
    public function status(): StatusPlano { return $this->status; }
    public function criadoEm(): ?DateTimeImmutable { return $this->criadoEm; }
    public function atualizadoEm(): ?DateTimeImmutable { return $this->atualizadoEm; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'projeto' => $this->projeto,
            'versao' => $this->versao,
            'responsavel' => $this->responsavel,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'criado_em' => $this->criadoEm?->format(DATE_ATOM),
            'atualizado_em' => $this->atualizadoEm?->format(DATE_ATOM),
        ];
    }
}
