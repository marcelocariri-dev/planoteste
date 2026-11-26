<?php

namespace Src\Domain\RegraNegocio\Enums;

/**
 * Tags que o PM/PO marca no texto da regra.
 * Sintaxe no editor:
 *   ::: caso [alta] Título do caso
 *   Pré-condições: ...
 *   Passos:
 *   1. ...
 *   Resultado esperado: ...
 *   :::
 */
enum TipoMarcador: string
{
    case CASO = 'caso';
    case REGRA = 'regra';
    case CRITERIO = 'criterio';
    case EXCECAO = 'excecao';
    case DUVIDA = 'duvida';

    public function rotulo(): string
    {
        return match ($this) {
            self::CASO => 'Caso de teste',
            self::REGRA => 'Regra',
            self::CRITERIO => 'Critério de aceite',
            self::EXCECAO => 'Exceção',
            self::DUVIDA => 'Dúvida',
        };
    }
}
