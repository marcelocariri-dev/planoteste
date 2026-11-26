<?php

namespace Src\Infrastructure\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Application\Shared\ArmazenamentoArquivosInterface;
use Src\Application\Shared\ArquivoRecebido;

/**
 * Guarda os arquivos do cofre no disco local do Laravel: storage/app/private/cofre/{TAG-DO-MODULO}/...
 * (no docker essa pasta fica dentro do volume ./backend, então persiste entre reinícios).
 */
final class ArmazenamentoLocal implements ArmazenamentoArquivosInterface
{
    private const RAIZ = 'cofre';

    public function guardar(ArquivoRecebido $arquivo, string $pasta): string
    {
        $extensao = strtolower(pathinfo($arquivo->nomeOriginal, PATHINFO_EXTENSION));
        $base = Str::slug(pathinfo($arquivo->nomeOriginal, PATHINFO_FILENAME)) ?: 'arquivo';
        $nome = now()->format('Ymd-His') . '-' . Str::random(6) . '-' . Str::limit($base, 80, '') . ($extensao ? ".{$extensao}" : '');
        $destino = self::RAIZ . '/' . preg_replace('/[^A-Z0-9-]/', '', strtoupper($pasta)) . '/' . $nome;

        $stream = fopen($arquivo->caminhoTemporario, 'rb');
        try {
            Storage::disk('local')->writeStream($destino, $stream);
        } finally {
            is_resource($stream) && fclose($stream);
        }

        return $destino;
    }

    public function conteudo(string $caminho): string
    {
        return Storage::disk('local')->get($caminho) ?? '';
    }

    public function caminhoAbsoluto(string $caminho): string
    {
        return Storage::disk('local')->path($caminho);
    }

    public function existe(string $caminho): bool
    {
        return Storage::disk('local')->exists($caminho);
    }

    public function remover(string $caminho): void
    {
        Storage::disk('local')->delete($caminho);
    }
}
