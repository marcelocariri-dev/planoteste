<?php

namespace Src\Domain\Backlog\ValueObjects;

/** História de usuário no padrão "Como / Quero / Para". */
final class Historia
{
    /** @param string[] $criteriosAceite */
    public function __construct(
        public readonly string $codigo,
        public readonly string $titulo,
        public readonly string $comoUm,
        public readonly string $quero,
        public readonly string $para,
        public readonly array $criteriosAceite,
        public readonly string $prioridade,
        public readonly ?int $estimativa,
        public readonly ?string $observacoes,
    ) {
    }

    public function toArray(): array
    {
        return [
            'codigo' => $this->codigo,
            'titulo' => $this->titulo,
            'como_um' => $this->comoUm,
            'quero' => $this->quero,
            'para' => $this->para,
            'criterios_aceite' => $this->criteriosAceite,
            'prioridade' => $this->prioridade,
            'estimativa' => $this->estimativa,
            'observacoes' => $this->observacoes,
        ];
    }
}
