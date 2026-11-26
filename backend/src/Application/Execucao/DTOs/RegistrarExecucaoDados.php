<?php

namespace Src\Application\Execucao\DTOs;

use Src\Domain\Execucao\Enums\ResultadoExecucao;
use Src\Domain\Shared\Exceptions\DomainException;

final class RegistrarExecucaoDados
{
    public function __construct(
        public readonly ResultadoExecucao $resultado,
        public readonly ?string $observacao = null,
        public readonly ?string $executadoPor = null,
    ) {
    }

    public static function deArray(array $dados): self
    {
        $resultado = ResultadoExecucao::tryFrom((string) ($dados['resultado'] ?? ''))
            ?? throw new DomainException('Resultado de execução inválido.');

        return new self($resultado, $dados['observacao'] ?? null, $dados['executado_por'] ?? null);
    }
}
