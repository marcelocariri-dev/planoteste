<?php

namespace Src\Domain\RegraNegocio\ValueObjects;

use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\RegraNegocio\Enums\TipoMarcador;

/** Trecho da regra marcado com uma tag. */
final class Marcador
{
    public function __construct(
        public readonly TipoMarcador $tipo,
        public readonly string $titulo,
        public readonly string $conteudo,
        public readonly int $linha,
        public readonly ?Prioridade $prioridade = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->rotulo(),
            'titulo' => $this->titulo,
            'conteudo' => $this->conteudo,
            'linha' => $this->linha,
            'prioridade' => $this->prioridade?->value,
        ];
    }
}
