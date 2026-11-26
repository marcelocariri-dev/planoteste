<?php

namespace Src\Domain\IntegracaoMcp\ValueObjects;

final class FerramentaMcp
{
    public function __construct(
        public readonly string $nome,
        public readonly ?string $descricao,
        public readonly array $esquemaEntrada,
    ) {
    }

    public function toArray(): array
    {
        return ['nome' => $this->nome, 'descricao' => $this->descricao, 'esquema_entrada' => (object) $this->esquemaEntrada];
    }
}
