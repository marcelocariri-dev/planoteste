<?php

namespace Src\Domain\PlanoTeste\Enums;

enum StatusPlano: string
{
    case RASCUNHO = 'rascunho';
    case EM_EXECUCAO = 'em_execucao';
    case CONCLUIDO = 'concluido';

    public function rotulo(): string
    {
        return match ($this) {
            self::RASCUNHO => 'Rascunho',
            self::EM_EXECUCAO => 'Em execução',
            self::CONCLUIDO => 'Concluído',
        };
    }

    public function permiteEdicao(): bool
    {
        return $this !== self::CONCLUIDO;
    }
}
