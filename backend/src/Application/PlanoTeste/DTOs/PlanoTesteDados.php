<?php

namespace Src\Application\PlanoTeste\DTOs;

final class PlanoTesteDados
{
    public function __construct(
        public readonly string $nome,
        public readonly ?string $descricao = null,
        public readonly ?string $projeto = null,
        public readonly ?string $versao = null,
        public readonly ?string $responsavel = null,
    ) {
    }

    public static function deArray(array $dados): self
    {
        return new self(
            nome: (string) ($dados['nome'] ?? ''),
            descricao: $dados['descricao'] ?? null,
            projeto: $dados['projeto'] ?? null,
            versao: $dados['versao'] ?? null,
            responsavel: $dados['responsavel'] ?? null,
        );
    }
}
