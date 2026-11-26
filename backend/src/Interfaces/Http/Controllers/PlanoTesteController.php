<?php

namespace Src\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\Application\PlanoTeste\DTOs\PlanoTesteDados;
use Src\Application\PlanoTeste\UseCases\AlterarStatusPlanoTeste;
use Src\Application\PlanoTeste\UseCases\AtualizarPlanoTeste;
use Src\Application\PlanoTeste\UseCases\CriarPlanoTeste;
use Src\Application\PlanoTeste\UseCases\ExcluirPlanoTeste;
use Src\Application\PlanoTeste\UseCases\ListarPlanosTeste;
use Src\Application\PlanoTeste\UseCases\ObterPlanoTeste;
use Src\Interfaces\Http\Requests\PlanoTesteRequest;

class PlanoTesteController
{
    public function index(Request $request, ListarPlanosTeste $useCase): JsonResponse
    {
        return response()->json([
            'data' => $useCase->executar($request->query('busca'), $request->query('status')),
        ]);
    }

    public function show(int $id, ObterPlanoTeste $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->executar($id)]);
    }

    public function store(PlanoTesteRequest $request, CriarPlanoTeste $useCase): JsonResponse
    {
        $plano = $useCase->executar(PlanoTesteDados::deArray($request->validated()));

        return response()->json(['data' => $plano], Response::HTTP_CREATED);
    }

    public function update(int $id, PlanoTesteRequest $request, AtualizarPlanoTeste $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->executar($id, PlanoTesteDados::deArray($request->validated()))]);
    }

    public function destroy(int $id, ExcluirPlanoTeste $useCase): Response
    {
        $useCase->executar($id);

        return response()->noContent();
    }

    public function concluir(int $id, AlterarStatusPlanoTeste $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->executar($id, AlterarStatusPlanoTeste::CONCLUIR)]);
    }

    public function reabrir(int $id, AlterarStatusPlanoTeste $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->executar($id, AlterarStatusPlanoTeste::REABRIR)]);
    }
}
