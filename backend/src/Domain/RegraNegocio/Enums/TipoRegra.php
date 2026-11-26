<?php

namespace Src\Domain\RegraNegocio\Enums;

enum TipoRegra: string
{
    case MELHORIA = 'melhoria';
    case NOVO_MODULO = 'novo_modulo';

    public function rotulo(): string
    {
        return match ($this) {
            self::MELHORIA => 'Melhoria',
            self::NOVO_MODULO => 'Novo módulo',
        };
    }
}
