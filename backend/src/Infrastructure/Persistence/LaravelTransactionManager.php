<?php

namespace Src\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Application\Shared\TransactionManagerInterface;

final class LaravelTransactionManager implements TransactionManagerInterface
{
    public function executar(callable $operacao): mixed
    {
        return DB::transaction($operacao);
    }
}
