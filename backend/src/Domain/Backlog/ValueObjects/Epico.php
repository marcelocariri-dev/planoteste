<?php

namespace Src\Domain\Backlog\ValueObjects;

final class Epico
{
    /** @param Historia[] $historias */
    public function __construct(
        public readonly string $codigo,
        public readonly string $titulo,
        public readonly ?string $descricao,
        public readonly array $historias,
    ) {
    }

    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'historias' => array_map(fn (Historia $h) => $h->toArray(), $this->historias),
        ];
    }
}
