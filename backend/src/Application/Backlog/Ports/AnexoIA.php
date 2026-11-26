<?php

namespace Src\Application\Backlog\Ports;

final class AnexoIA
{
    public function __construct(
        public readonly string $nome,
        public readonly string $mimeType,
        public readonly string $conteudo, // bytes
    ) {
    }
}
