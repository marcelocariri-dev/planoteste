<?php

namespace Src\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Application\Shared\TransactionManagerInterface;
use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\Execucao\Repositories\ExecucaoRepositoryInterface;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Infrastructure\Persistence\Eloquent\Repositories\EloquentCasoTesteRepository;
use Src\Infrastructure\Persistence\Eloquent\Repositories\EloquentExecucaoRepository;
use Src\Infrastructure\Persistence\Eloquent\Repositories\EloquentPlanoTesteRepository;
use Src\Infrastructure\Persistence\LaravelTransactionManager;

/**
 * Liga as interfaces do domínio/aplicação às implementações de infraestrutura.
 */
class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        PlanoTesteRepositoryInterface::class => EloquentPlanoTesteRepository::class,
        CasoTesteRepositoryInterface::class => EloquentCasoTesteRepository::class,
        ExecucaoRepositoryInterface::class => EloquentExecucaoRepository::class,
        TransactionManagerInterface::class => LaravelTransactionManager::class,
    ];
}
