<?php

namespace Src\Domain\RegraNegocio\ValueObjects;

use DateTimeImmutable;
use Src\Domain\RegraNegocio\Enums\DecisaoValidacao;
use Src\Domain\RegraNegocio\Enums\PapelValidador;

/** Parecer de um validador (PM ou PO). Imutável. */
final class Validacao
{
    public function __construct(
        public readonly PapelValidador $papel,
        public readonly DecisaoValidacao $decisao = DecisaoValidacao::PENDENTE,
        public readonly ?string $responsavel = null,
        public readonly ?string $parecer = null,
        public readonly ?DateTimeImmutable $em = null,
    ) {
    }

    public static function pendente(PapelValidador $papel): self
    {
        return new self($papel);
    }

    public function toArray(): array
    {
        return [
            'papel' => $this->papel->value,
            'decisao' => $this->decisao->value,
            'decisao_rotulo' => $this->decisao->rotulo(),
            'responsavel' => $this->responsavel,
            'parecer' => $this->parecer,
            'em' => $this->em?->format(DATE_ATOM),
        ];
    }
}
