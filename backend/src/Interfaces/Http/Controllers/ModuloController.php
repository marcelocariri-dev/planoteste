<?php

namespace Src\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Modulo\UseCases\GerenciarModulos;

class ModuloController
{
    public function __construct(private readonly GerenciarModulos $modulos)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->modulos->listar()]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->modulos->salvar(null, $this->validar($request))], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        return response()->json(['data' => $this->modulos->salvar($id, $this->validar($request))]);
    }

    public function destroy(int $id)
    {
        $this->modulos->excluir($id);

        return response()->noContent();
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:40'],
            'descricao' => ['nullable', 'string'],
            'cor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
    }
}
