<?php

namespace Src\Domain\CasoTeste\Enums;

enum OrigemCaso: string
{
    case MANUAL = 'manual';
    case REGRA = 'regra';
    case MCP = 'mcp';
    case BACKLOG = 'backlog';

    public function rotulo(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::REGRA => 'Regra de negócio',
            self::MCP => 'MCP (oficial)',
            self::BACKLOG => 'Product backlog',
        };
    }
}
