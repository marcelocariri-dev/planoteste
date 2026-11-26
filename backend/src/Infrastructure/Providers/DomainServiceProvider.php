<?php

namespace Src\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Src\Application\Backlog\Ports\GeradorBacklogInterface;
use Src\Application\IntegracaoMcp\Ports\ClienteMcpInterface;
use Src\Application\Shared\ArmazenamentoArquivosInterface;
use Src\Application\Shared\TransactionManagerInterface;
use Src\Domain\Backlog\Repositories\BacklogRepositoryInterface;
use Src\Domain\Backlog\Repositories\PadraoBacklogRepositoryInterface;
use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\Cofre\Repositories\CategoriaRepositoryInterface;
use Src\Domain\Cofre\Repositories\DocumentoRepositoryInterface;
use Src\Domain\Execucao\Repositories\ExecucaoRepositoryInterface;
use Src\Domain\IntegracaoMcp\Repositories\ConexaoMcpRepositoryInterface;
use Src\Domain\Modulo\Repositories\ModuloRepositoryInterface;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\RegraNegocio\Repositories\RegraNegocioRepositoryInterface;
use Src\Infrastructure\IA\ClaudeGeradorBacklog;
use Src\Infrastructure\Mcp\ClienteMcpHttp;
use Src\Infrastructure\Persistence\Eloquent\Repositories as Repo;
use Src\Infrastructure\Persistence\LaravelTransactionManager;
use Src\Infrastructure\Storage\ArmazenamentoLocal;

/**
 * Liga as interfaces do domínio/aplicação às implementações de infraestrutura.
 */
class DomainServiceProvider extends ServiceProvider
{
    public array $bindings = [
        PlanoTesteRepositoryInterface::class => Repo\EloquentPlanoTesteRepository::class,
        CasoTesteRepositoryInterface::class => Repo\EloquentCasoTesteRepository::class,
        ExecucaoRepositoryInterface::class => Repo\EloquentExecucaoRepository::class,
        ModuloRepositoryInterface::class => Repo\EloquentModuloRepository::class,
        RegraNegocioRepositoryInterface::class => Repo\EloquentRegraNegocioRepository::class,
        CategoriaRepositoryInterface::class => Repo\EloquentCategoriaRepository::class,
        DocumentoRepositoryInterface::class => Repo\EloquentDocumentoRepository::class,
        PadraoBacklogRepositoryInterface::class => Repo\EloquentPadraoBacklogRepository::class,
        BacklogRepositoryInterface::class => Repo\EloquentBacklogRepository::class,
        ConexaoMcpRepositoryInterface::class => Repo\EloquentConexaoMcpRepository::class,
        TransactionManagerInterface::class => LaravelTransactionManager::class,
        ArmazenamentoArquivosInterface::class => ArmazenamentoLocal::class,
        ClienteMcpInterface::class => ClienteMcpHttp::class,
    ];

    public function register(): void
    {
        $this->app->bind(GeradorBacklogInterface::class, fn () => new ClaudeGeradorBacklog(
            config('services.anthropic.key'),
            config('services.anthropic.model'),
            (int) config('services.anthropic.max_tokens'),
        ));
    }
}
