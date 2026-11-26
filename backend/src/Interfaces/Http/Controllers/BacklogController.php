<?php

namespace Src\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Backlog\UseCases\GerenciarBacklog;

class BacklogController
{
    public function __construct(private readonly GerenciarBacklog $backlog)
    {
    }

    public function configuracao(): JsonResponse
    {
        return response()->json(['data' => $this->backlog->configuracao()]);
    }

    public function padroes(): JsonResponse
    {
        return response()->json(['data' => $this->backlog->listarPadroes()]);
    }

    public function salvarPadrao(Request $request, ?int $id = null): JsonResponse
    {
        $d = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'instrucoes' => ['required', 'string'],
        ]);

        return response()->json(['data' => $this->backlog->salvarPadrao($id, $d)], $id ? 200 : 201);
    }

    public function excluirPadrao(int $id)
    {
        $this->backlog->excluirPadrao($id);

        return response()->noContent();
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->backlog->listar($request->integer('modulo_id') ?: null)]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->backlog->obter($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'padrao_id' => ['required', 'integer'],
            'modulo_id' => ['nullable', 'integer'],
            'orientacoes' => ['required', 'string'],
            'documento_ids' => ['nullable', 'array'],
            'documento_ids.*' => ['integer'],
            'regra_ids' => ['nullable', 'array'],
            'regra_ids.*' => ['integer'],
        ]);

        return response()->json(['data' => $this->backlog->gerar($d)], 201);
    }

    public function importarResposta(int $id, Request $request): JsonResponse
    {
        $request->validate(['resposta' => ['required', 'string']]);

        return response()->json(['data' => $this->backlog->importarResposta($id, $request->input('resposta'))]);
    }

    public function destroy(int $id)
    {
        $this->backlog->excluir($id);

        return response()->noContent();
    }

    public function gerarPlano(int $id, Request $request): JsonResponse
    {
        $request->validate(['plano_id' => ['nullable', 'integer'], 'historias' => ['nullable', 'array'], 'historias.*' => ['string']]);

        return response()->json(['data' => $this->backlog->gerarPlano(
            $id, $request->integer('plano_id') ?: null, $request->has('historias') ? (array) $request->input('historias') : null,
        )], 201);
    }
}
