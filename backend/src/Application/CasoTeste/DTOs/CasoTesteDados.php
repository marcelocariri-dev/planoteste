<?php

namespace Src\Application\CasoTeste\DTOs;

use Src\Domain\CasoTeste\Enums\Prioridade;

final class CasoTesteDados
{
    public function __construct(
        public readonly string $titulo,
        public readonly string $passos,
        public readonly string $resultadoEsperado,
        public readonly Prioridade $prioridade = Prioridade::MEDIA,
        public readonly ?string $preCondicoes = null,
        public readonly ?int $ordem = null,
    ) {
    }

    public static function deArray(array $dados): self
    {
        return new self(
            titulo: (string) ($dados['titulo'] ?? ''),
            passos: (string) ($dados['passos'] ?? ''),
            resultadoEsperado: (string) ($dados['resultado_esperado'] ?? ''),
            prioridade: Prioridade::tryFrom((string) ($dados['prioridade'] ?? '')) ?? Prioridade::MEDIA,
            preCondicoes: $dados['pre_condicoes'] ?? null,
            ordem: isset($dados['ordem']) ? (int) $dados['ordem'] : null,
        );
    }
}
