<?php

namespace Src\Domain\RegraNegocio\Enums;

enum DecisaoValidacao: string
{
    case PENDENTE = 'pendente';
    case APROVADO = 'aprovado';
    case REPROVADO = 'reprovado';

    public function rotulo(): string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::APROVADO => 'Aprovado',
            self::REPROVADO => 'Reprovado',
        };
    }
}
