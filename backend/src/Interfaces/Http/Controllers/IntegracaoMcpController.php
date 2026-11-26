<?php

namespace Src\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\IntegracaoMcp\UseCases\GerenciarIntegracaoMcp;

class IntegracaoMcpController
{
    public function __construct(private readonly GerenciarIntegracaoMcp $mcp)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->mcp->listar()]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->mcp->salvar(null, $this->validar($request))], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        return response()->json(['data' => $this->mcp->salvar($id, $this->validar($request))]);
    }

    public function destroy(int $id)
    {
        $this->mcp->excluir($id);

        return response()->noContent();
    }

    public function testar(int $id): JsonResponse
    {
        return response()->json(['data' => $this->mcp->testar($id)]);
    }

    public function buscarCasos(int $id, Request $request): JsonResponse
    {
        $request->validate(['ferramenta' => ['nullable', 'string'], 'argumentos' => ['nullable', 'array']]);

        return response()->json(['data' => $this->mcp->buscarCasos(
            $id, $request->input('ferramenta'), $request->has('argumentos') ? (array) $request->input('argumentos') : null,
        )]);
    }

    public function importar(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'casos' => ['required', 'array', 'min:1'],
            'casos.*.titulo' => ['required', 'string', 'max:200'],
            'casos.*.passos' => ['required', 'string'],
            'casos.*.resultado_esperado' => ['required', 'string'],
            'plano_id' => ['nullable', 'integer'],
            'novo_plano' => ['nullable', 'array'],
            'novo_plano.nome' => ['required_without:plano_id', 'string', 'max:150'],
        ]);

        return response()->json(['data' => $this->mcp->importar(
            $id, $request->input('casos'), $request->integer('plano_id') ?: null, $request->input('novo_plano'),
        )], 201);
    }

    private function validar(Request $request): array
    {
        $d = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'url' => ['required', 'string', 'max:500'],
            'token' => ['nullable', 'string'],
            'cabecalhos' => ['nullable'],
            'ferramenta_casos' => ['nullable', 'string', 'max:150'],
            'argumentos_padrao' => ['nullable'],
            'ativo' => ['nullable', 'boolean'],
        ]);
        // token ausente no PUT = manter o atual
        if (! $request->exists('token')) {
            unset($d['token']);
        }
        if (! $request->exists('cabecalhos')) {
            unset($d['cabecalhos']);
        }

        return $d;
    }
}
