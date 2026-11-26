<?php

namespace Src\Application\PlanoTeste\UseCases;

use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

/**
 * Concluir ou reabrir um plano.
 */
final class AlterarStatusPlanoTeste
{
    public const CONCLUIR = 'concluir';
    public const REABRIR = 'reabrir';

    public function __construct(private readonly PlanoTesteRepositoryInterface $planos)
    {
    }

    public function executar(int $id, string $acao): array
    {
        $plano = $this->planos->buscarPorId($id)
            ?? throw EntidadeNaoEncontradaException::para('Plano de teste', $id);

        match ($acao) {
            self::CONCLUIR => $plano->concluir(),
            self::REABRIR => $plano->reabrir(),
            default => throw new DomainException("Ação inválida: {$acao}."),
        };

        return $this->planos->salvar($plano)->toArray();
    }
}
