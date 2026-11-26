<?php

namespace Src\Domain\Backlog\Entities;

use DateTimeImmutable;
use Src\Domain\Backlog\Enums\StatusBacklog;
use Src\Domain\Backlog\ValueObjects\Epico;
use Src\Domain\Shared\Exceptions\DomainException;

final class Backlog
{
    /**
     * @param int[] $documentoIds
     * @param int[] $regraIds
     * @param Epico[] $epicos
     */
    private function __construct(
        private ?int $id,
        private string $titulo,
        private ?int $moduloId,
        private ?int $padraoId,
        private string $orientacoes,
        private array $documentoIds,
        private array $regraIds,
        private string $prompt,
        private StatusBacklog $status,
        private array $epicos = [],
        private ?string $respostaBruta = null,
        private ?string $modelo = null,
        private ?string $erro = null,
        private ?DateTimeImmutable $criadoEm = null,
    ) {
        if ($this->titulo === '' || mb_strlen($this->titulo) > 200) {
            throw new DomainException('O título do backlog é obrigatório (máx. 200 caracteres).');
        }
        if (trim($this->orientacoes) === '') {
            throw new DomainException('Escreva as orientações para gerar o backlog.');
        }
    }

    public static function solicitar(
        string $titulo, ?int $moduloId, ?int $padraoId, string $orientacoes, array $documentoIds, array $regraIds, string $prompt,
    ): self {
        return new self(null, trim($titulo), $moduloId, $padraoId, $orientacoes,
            array_values(array_map('intval', $documentoIds)), array_values(array_map('intval', $regraIds)),
            $prompt, StatusBacklog::AGUARDANDO_IA);
    }

    public static function restaurar(
        int $id, string $titulo, ?int $moduloId, ?int $padraoId, string $orientacoes, array $documentoIds, array $regraIds,
        string $prompt, StatusBacklog $status, array $epicos, ?string $respostaBruta, ?string $modelo, ?string $erro,
        ?DateTimeImmutable $criadoEm,
    ): self {
        return new self($id, $titulo, $moduloId, $padraoId, $orientacoes, $documentoIds, $regraIds, $prompt, $status,
            $epicos, $respostaBruta, $modelo, $erro, $criadoEm);
    }

    /** @param Epico[] $epicos */
    public function registrarResultado(array $epicos, string $respostaBruta, ?string $modelo): void
    {
        if ($epicos === []) {
            throw new DomainException('A resposta não trouxe nenhum épico.');
        }
        $this->epicos = $epicos;
        $this->respostaBruta = $respostaBruta;
        $this->modelo = $modelo;
        $this->erro = null;
        $this->status = StatusBacklog::GERADO;
    }

    public function registrarFalha(string $mensagem, ?string $respostaBruta = null): void
    {
        $this->erro = $mensagem;
        $this->respostaBruta = $respostaBruta ?? $this->respostaBruta;
        $this->status = StatusBacklog::ERRO;
    }

    public function totalHistorias(): int
    {
        return array_sum(array_map(fn (Epico $e) => count($e->historias), $this->epicos));
    }

    public function definirId(int $id): void { $this->id ??= $id; }
    public function id(): ?int { return $this->id; }
    public function titulo(): string { return $this->titulo; }
    public function moduloId(): ?int { return $this->moduloId; }
    public function padraoId(): ?int { return $this->padraoId; }
    public function orientacoes(): string { return $this->orientacoes; }
    public function documentoIds(): array { return $this->documentoIds; }
    public function regraIds(): array { return $this->regraIds; }
    public function prompt(): string { return $this->prompt; }
    public function status(): StatusBacklog { return $this->status; }
    /** @return Epico[] */
    public function epicos(): array { return $this->epicos; }
    public function respostaBruta(): ?string { return $this->respostaBruta; }
    public function modelo(): ?string { return $this->modelo; }
    public function erro(): ?string { return $this->erro; }
    public function criadoEm(): ?DateTimeImmutable { return $this->criadoEm; }

    public function toArray(bool $completo = true): array
    {
        $base = [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'modulo_id' => $this->moduloId,
            'padrao_id' => $this->padraoId,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'total_epicos' => count($this->epicos),
            'total_historias' => $this->totalHistorias(),
            'modelo' => $this->modelo,
            'erro' => $this->erro,
            'criado_em' => $this->criadoEm?->format(DATE_ATOM),
        ];

        if (! $completo) {
            return $base;
        }

        return $base + [
            'orientacoes' => $this->orientacoes,
            'documento_ids' => $this->documentoIds,
            'regra_ids' => $this->regraIds,
            'prompt' => $this->prompt,
            'resposta_bruta' => $this->respostaBruta,
            'epicos' => array_map(fn (Epico $e) => $e->toArray(), $this->epicos),
        ];
    }
}
