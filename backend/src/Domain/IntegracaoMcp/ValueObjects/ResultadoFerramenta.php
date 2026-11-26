<?php

namespace Src\Domain\IntegracaoMcp\ValueObjects;

/** Resultado de tools/call: blocos de texto e, se houver, structuredContent. */
final class ResultadoFerramenta
{
    /** @param string[] $textos */
    public function __construct(
        public readonly array $textos,
        public readonly ?array $conteudoEstruturado,
        public readonly bool $erro,
    ) {
    }
}
