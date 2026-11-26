<?php

namespace Src\Domain\RegraNegocio\Enums;

enum StatusRegra: string
{
    case RASCUNHO = 'rascunho';
    case EM_VALIDACAO = 'em_validacao';
    case APROVADA = 'aprovada';
    case REPROVADA = 'reprovada';

    public function rotulo(): string
    {
        return match ($this) {
            self::RASCUNHO => 'Rascunho',
            self::EM_VALIDACAO => 'Em validação',
            self::APROVADA => 'Aprovada',
            self::REPROVADA => 'Reprovada',
        };
    }

    public function permiteEdicao(): bool
    {
        return $this === self::RASCUNHO || $this === self::REPROVADA;
    }
}
