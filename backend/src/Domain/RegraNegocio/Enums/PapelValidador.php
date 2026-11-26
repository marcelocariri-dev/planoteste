<?php

namespace Src\Domain\RegraNegocio\Enums;

enum PapelValidador: string
{
    case PM = 'pm';
    case PO = 'po';

    public function rotulo(): string
    {
        return strtoupper($this->value);
    }
}
