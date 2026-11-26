<?php

namespace Src\Domain\Shared\Exceptions;

/**
 * Entidade solicitada não existe. A camada de interface converte em HTTP 404.
 */
class EntidadeNaoEncontradaException extends \RuntimeException
{
    public static function para(string $entidade, int $id): self
    {
        return new self(sprintf('%s #%d não encontrado(a).', $entidade, $id));
    }
}
