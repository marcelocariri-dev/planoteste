<?php

namespace Src\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Src\Application\RegraNegocio\UseCases\GerenciarRegras;
use Src\Domain\RegraNegocio\Enums\DecisaoValidacao;
use Src\Domain\RegraNegocio\Enums\PapelValidador;
use Src\Domain\RegraNegocio\Enums\TipoRegra;

class RegraNegocioController
{
    public function __construct(private readonly GerenciarRegras $regras)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->regras->listar(
            $request->integer('modulo_id') ?: null,
            $request->query('status'),
            $request->query('tipo'),
            $request->query('busca'),
        )]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->regras->obter($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->regras->criar($this->validar($request))], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        return response()->json(['data' => $this->regras->editar($id, $this->validar($request))]);
    }

    public function destroy(int $id)
    {
        $this->regras->excluir($id);

        return response()->noContent();
    }

    public function analisar(Request $request): JsonResponse
    {
        $request->validate(['conteudo' => ['nullable', 'string']]);

        return response()->json(['data' => $this->regras->analisar((string) $request->input('conteudo', ''))]);
    }

    public function fluxo(int $id, string $acao): JsonResponse
    {
        return response()->json(['data' => $this->regras->alterarFluxo($id, $acao)]);
    }

    public function parecer(int $id, Request $request): JsonResponse
    {
        $dados = $request->validate([
            'papel' => ['required', Rule::enum(PapelValidador::class)],
            'decisao' => ['required', Rule::in([DecisaoValidacao::APROVADO->value, DecisaoValidacao::REPROVADO->value])],
            'responsavel' => ['required', 'string', 'max:100'],
            'parecer' => ['nullable', 'string'],
        ]);

        return response()->json(['data' => $this->regras->registrarParecer($id, $dados)]);
    }

    public function versoes(int $id): JsonResponse
    {
        return response()->json(['data' => $this->regras->versoes($id)]);
    }

    public function gerarPlano(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'plano_id' => ['nullable', 'integer'],
            'indices' => ['nullable', 'array'],
            'indices.*' => ['integer', 'min:0'],
        ]);

        return response()->json(['data' => $this->regras->gerarPlano(
            $id,
            $request->integer('plano_id') ?: null,
            $request->has('indices') ? array_map('intval', (array) $request->input('indices')) : null,
        )], 201);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'modulo_id' => ['required', 'integer'],
            'titulo' => ['required', 'string', 'max:200'],
            'tipo' => ['required', Rule::enum(TipoRegra::class)],
            'conteudo' => ['nullable', 'string'],
            'autor' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
