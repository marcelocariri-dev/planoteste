<?php

namespace Src\Domain\Shared\Exceptions;

/**
 * Violação de regra de negócio. A camada de interface converte em HTTP 422.
 */
class DomainException extends \DomainException
{
}
