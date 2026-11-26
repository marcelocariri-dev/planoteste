<?php

namespace Src\Domain\Shared\Support;

final class Texto
{
    /** "Ponto de Venda (PDV)" -> "PONTO-DE-VENDA-PDV" */
    public static function tag(string $valor): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
        $tag = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $semAcento));

        return trim($tag, '-');
    }

    public static function vazioParaNull(?string $valor): ?string
    {
        $valor = $valor !== null ? trim($valor) : null;

        return $valor === '' ? null : $valor;
    }
}
