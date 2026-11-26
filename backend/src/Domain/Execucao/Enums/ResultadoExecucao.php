<?php

namespace Src\Domain\Execucao\Enums;

enum ResultadoExecucao: string
{
    case PASSOU = 'passou';
    case FALHOU = 'falhou';
    case BLOQUEADO = 'bloqueado';

    public function rotulo(): string
    {
        return match ($this) {
            self::PASSOU => 'Passou',
            self::FALHOU => 'Falhou',
            self::BLOQUEADO => 'Bloqueado',
        };
    }

    /** Falhas e bloqueios exigem explicação. */
    public function exigeObservacao(): bool
    {
        return $this !== self::PASSOU;
    }
}
