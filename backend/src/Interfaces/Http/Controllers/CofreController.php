<?php

namespace Src\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Application\Cofre\UseCases\GerenciarCofre;
use Src\Application\Shared\ArquivoRecebido;

class CofreController
{
    public function __construct(private readonly GerenciarCofre $cofre)
    {
    }

    public function categorias(): JsonResponse
    {
        return response()->json(['data' => $this->cofre->listarCategorias()]);
    }

    public function salvarCategoria(Request $request, ?int $id = null): JsonResponse
    {
        $d = $request->validate(['nome' => ['required', 'string', 'max:80'], 'descricao' => ['nullable', 'string', 'max:255']]);

        return response()->json(['data' => $this->cofre->salvarCategoria($id, $d['nome'], $d['descricao'] ?? null)], $id ? 200 : 201);
    }

    public function excluirCategoria(int $id)
    {
        $this->cofre->excluirCategoria($id);

        return response()->noContent();
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->cofre->listar([
            'busca' => $request->query('busca'),
            'categoria_id' => $request->integer('categoria_id') ?: null,
            'modulo_id' => $request->integer('modulo_id') ?: null,
            'regra_id' => $request->integer('regra_id') ?: null,
            'sem_modulo' => $request->boolean('sem_modulo'),
        ])]);
    }

    /** multipart/form-data: arquivo + categoria_id (+ modulo_id | regra_id, titulo, descricao, tags, enviado_por) */
    public function store(Request $request): JsonResponse
    {
        $d = $request->validate([
            'arquivo' => ['required', 'file', 'max:25600'],
            'categoria_id' => ['required', 'integer'],
            'modulo_id' => ['nullable', 'integer'],
            'regra_id' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:200'],
            'descricao' => ['nullable', 'string'],
            'tags' => ['nullable'],
            'enviado_por' => ['nullable', 'string', 'max:100'],
        ]);

        $arquivo = $request->file('arquivo');
        $recebido = new ArquivoRecebido(
            $arquivo->getRealPath(),
            $arquivo->getClientOriginalName(),
            $arquivo->getMimeType() ?: 'application/octet-stream',
            (int) $arquivo->getSize(),
        );
        unset($d['arquivo']);

        return response()->json(['data' => $this->cofre->enviar($recebido, $d)], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $d = $request->validate([
            'titulo' => ['required', 'string', 'max:200'],
            'descricao' => ['nullable', 'string'],
            'categoria_id' => ['required', 'integer'],
            'modulo_id' => ['nullable', 'integer'],
            'tags' => ['nullable'],
        ]);

        return response()->json(['data' => $this->cofre->atualizar($id, $d)]);
    }

    public function destroy(int $id)
    {
        $this->cofre->excluir($id);

        return response()->noContent();
    }

    public function download(int $id, Request $request)
    {
        $a = $this->cofre->arquivoParaDownload($id);

        return $request->boolean('inline')
            ? response()->file($a['caminho'], ['Content-Type' => $a['mime'], 'Content-Disposition' => 'inline; filename="' . addslashes($a['nome']) . '"'])
            : response()->download($a['caminho'], $a['nome'], ['Content-Type' => $a['mime']]);
    }
}
