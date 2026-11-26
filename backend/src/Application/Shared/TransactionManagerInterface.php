<?php

namespace Src\Application\Shared;

/**
 * Abstração de transação para que os casos de uso não dependam do framework.
 */
interface TransactionManagerInterface
{
    /**
     * @template T
     * @param  callable(): T  $operacao
     * @return T
     */
    public function executar(callable $operacao): mixed;
}
