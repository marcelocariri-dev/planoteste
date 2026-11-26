<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Regras de negócio violadas -> 422
        $exceptions->render(fn (DomainException $e, Request $request) => response()->json([
            'message' => $e->getMessage(),
        ], 422));

        // Entidade inexistente -> 404
        $exceptions->render(fn (EntidadeNaoEncontradaException $e, Request $request) => response()->json([
            'message' => $e->getMessage(),
        ], 404));

        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
    })->create();
