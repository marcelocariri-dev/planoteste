<?php

namespace Src\Application\Backlog\Ports;

final class RespostaIA
{
    /** @param string[] $avisos */
    public function __construct(
        public readonly string $texto,
        public readonly string $modelo,
        public readonly array $avisos = [],
    ) {
    }
}
