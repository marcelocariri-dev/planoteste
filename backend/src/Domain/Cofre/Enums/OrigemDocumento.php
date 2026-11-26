<?php

namespace Src\Domain\Cofre\Enums;

enum OrigemDocumento: string
{
    case AVULSO = 'avulso';          // enviado direto no cofre
    case VALIDACAO = 'validacao';    // anexado em uma regra de negócio

    public function rotulo(): string
    {
        return match ($this) {
            self::AVULSO => 'Upload avulso',
            self::VALIDACAO => 'Validação de regra',
        };
    }
}
