<?php

namespace Src\Domain\Backlog\Enums;

enum StatusBacklog: string
{
    case AGUARDANDO_IA = 'aguardando_ia'; // sem API configurada: prompt pronto para colar no Claude
    case GERADO = 'gerado';
    case ERRO = 'erro';

    public function rotulo(): string
    {
        return match ($this) {
            self::AGUARDANDO_IA => 'Aguardando resposta',
            self::GERADO => 'Gerado',
            self::ERRO => 'Erro',
        };
    }
}
