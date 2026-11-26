<?php

namespace Src\Domain\CasoTeste\Enums;

enum Prioridade: string
{
    case BAIXA = 'baixa';
    case MEDIA = 'media';
    case ALTA = 'alta';
    case CRITICA = 'critica';

    public function rotulo(): string
    {
        return match ($this) {
            self::BAIXA => 'Baixa',
            self::MEDIA => 'Média',
            self::ALTA => 'Alta',
            self::CRITICA => 'Crítica',
        };
    }
}
