<?php

namespace Src\Domain\Execucao\Entities;

use DateTimeImmutable;
use Src\Domain\Execucao\Enums\ResultadoExecucao;
use Src\Domain\Shared\Exceptions\DomainException;

/**
 * Registro imutável de uma execução de um caso de teste.
 */
final class Execucao
{
    private function __construct(
        private ?int $id,
        private int $casoId,
        private ResultadoExecucao $resultado,
        private ?string $observacao,
        private ?string $executadoPor,
        private DateTimeImmutable $executadoEm,
    ) {
        $this->validar();
    }

    public static function registrar(
        int $casoId,
        ResultadoExecucao $resultado,
        ?string $observacao = null,
        ?string $executadoPor = null,
        ?DateTimeImmutable $executadoEm = null,
    ): self {
        $observacao = $observacao !== null ? trim($observacao) : null;

        return new self(null, $casoId, $resultado, $observacao ?: null, $executadoPor, $executadoEm ?? new DateTimeImmutable());
    }

    public static function restaurar(
        int $id,
        int $casoId,
        ResultadoExecucao $resultado,
        ?string $observacao,
        ?string $executadoPor,
        DateTimeImmutable $executadoEm,
    ): self {
        return new self($id, $casoId, $resultado, $observacao, $executadoPor, $executadoEm);
    }

    private function validar(): void
    {
        if ($this->resultado->exigeObservacao() && empty($this->observacao)) {
            throw new DomainException(sprintf(
                'Informe uma observação quando o resultado for "%s".',
                $this->resultado->rotulo(),
            ));
        }
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new DomainException('A execução já possui identificador.');
        }
        $this->id = $id;
    }

    public function id(): ?int { return $this->id; }
    public function casoId(): int { return $this->casoId; }
    public function resultado(): ResultadoExecucao { return $this->resultado; }
    public function observacao(): ?string { return $this->observacao; }
    public function executadoPor(): ?string { return $this->executadoPor; }
    public function executadoEm(): DateTimeImmutable { return $this->executadoEm; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'caso_id' => $this->casoId,
            'resultado' => $this->resultado->value,
            'resultado_rotulo' => $this->resultado->rotulo(),
            'observacao' => $this->observacao,
            'executado_por' => $this->executadoPor,
            'executado_em' => $this->executadoEm->format(DATE_ATOM),
        ];
    }
}
