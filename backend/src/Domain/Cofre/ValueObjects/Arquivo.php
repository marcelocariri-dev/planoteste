<?php

namespace Src\Domain\Cofre\ValueObjects;

use Src\Domain\Shared\Exceptions\DomainException;

/** Metadados do arquivo físico guardado no cofre. */
final class Arquivo
{
    public const TAMANHO_MAXIMO = 25 * 1024 * 1024; // 25 MB

    public function __construct(
        public readonly string $caminho,
        public readonly string $nomeOriginal,
        public readonly string $mimeType,
        public readonly int $tamanho,
    ) {
        if ($tamanho <= 0) {
            throw new DomainException('Arquivo vazio.');
        }
        if ($tamanho > self::TAMANHO_MAXIMO) {
            throw new DomainException('Arquivo maior que 25 MB.');
        }
    }

    public function extensao(): string
    {
        return strtolower(pathinfo($this->nomeOriginal, PATHINFO_EXTENSION));
    }

    public function toArray(): array
    {
        return [
            'nome_original' => $this->nomeOriginal,
            'mime_type' => $this->mimeType,
            'tamanho' => $this->tamanho,
            'extensao' => $this->extensao(),
        ];
    }
}
