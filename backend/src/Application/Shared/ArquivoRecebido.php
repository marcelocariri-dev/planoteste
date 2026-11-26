<?php

namespace Src\Application\Shared;

/** Arquivo recebido pela interface (upload), independente do framework. */
final class ArquivoRecebido
{
    public function __construct(
        public readonly string $caminhoTemporario,
        public readonly string $nomeOriginal,
        public readonly string $mimeType,
        public readonly int $tamanho,
    ) {
    }
}
