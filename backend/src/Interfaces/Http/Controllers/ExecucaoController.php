<?php

namespace Src\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Src\Application\Execucao\DTOs\RegistrarExecucaoDados;
use Src\Application\Execucao\UseCases\ListarExecucoesDoCaso;
use Src\Application\Execucao\UseCases\RegistrarExecucao;
use Src\Interfaces\Http\Requests\RegistrarExecucaoRequest;

class ExecucaoController
{
    public function index(int $casoId, ListarExecucoesDoCaso $useCase): JsonResponse
    {
        return response()->json(['data' => $useCase->executar($casoId)]);
    }

    public function store(int $casoId, RegistrarExecucaoRequest $request, RegistrarExecucao $useCase): JsonResponse
    {
        $execucao = $useCase->executar($casoId, RegistrarExecucaoDados::deArray($request->validated()));

        return response()->json(['data' => $execucao], Response::HTTP_CREATED);
    }
}
