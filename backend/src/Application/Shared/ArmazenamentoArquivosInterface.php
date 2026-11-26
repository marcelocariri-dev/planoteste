<?php

namespace Src\Application\Shared;

interface ArmazenamentoArquivosInterface
{
    /** Copia o arquivo para o cofre dentro de $pasta e devolve o caminho relativo gravado. */
    public function guardar(ArquivoRecebido $arquivo, string $pasta): string;

    public function conteudo(string $caminho): string;

    public function caminhoAbsoluto(string $caminho): string;

    public function existe(string $caminho): bool;

    public function remover(string $caminho): void;
}
