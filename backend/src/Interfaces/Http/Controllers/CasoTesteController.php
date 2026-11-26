<?php

namespace Src\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Application\CasoTeste\DTOs\CasoTesteDados;
use Src\Application\CasoTeste\UseCases\AdicionarCasoTeste;
use Src\Application\CasoTeste\UseCases\AtualizarCasoTeste;
use Src\Application\CasoTeste\UseCases\RemoverCasoTeste;
use Src\Interfaces\Http\Requests\CasoTesteRequest;

class CasoTesteController
{
    public function store(int $planoId, CasoTesteRequest $request, AdicionarCasoTeste $useCase): JsonResponse
    {
        $caso = $useCase->executar($planoId, CasoTesteDados::deArray($request->validated()));

        return response()->json(['data' => $caso], Response::HTTP_CREATED);
    }

    public function update(int $id, CasoTesteRequest $request, AtualizarCasoTeste $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->executar($id, CasoTesteDados::deArray($request->validated()))]);
    }

    public function destroy(int $id, RemoverCasoTeste $useCase): Response
    {
        $useCase->executar($id);

        return response()->noContent();
    }
}
