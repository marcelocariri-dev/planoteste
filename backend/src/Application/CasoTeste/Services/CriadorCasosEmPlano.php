<?php

namespace Src\Application\CasoTeste\Services;

use Src\Application\Shared\TransactionManagerInterface;
use Src\Domain\CasoTeste\Entities\CasoTeste;
use Src\Domain\CasoTeste\Enums\OrigemCaso;
use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\PlanoTeste\Entities\PlanoTeste;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\RegraNegocio\ValueObjects\CasoExtraido;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

/**
 * Leva casos candidatos (de regra, MCP ou backlog) para um plano existente ou para um plano novo,
 * ignorando os que já estão no plano (mesma referência).
 */
final class CriadorCasosEmPlano
{
    public function __construct(
        private readonly PlanoTesteRepositoryInterface $planos,
        private readonly CasoTesteRepositoryInterface $casos,
        private readonly TransactionManagerInterface $transacao,
    ) {
    }

    /**
     * @param CasoExtraido[] $candidatos
     * @param callable(CasoExtraido, int): ?string $referencia  gera a referência de rastreio de cada caso
     * @param array{nome: string, projeto?: ?string, versao?: ?string, descricao?: ?string}|null $novoPlano
     * @return array{plano_id: int, criados: int, ignorados: int}
     */
    public function executar(array $candidatos, OrigemCaso $origem, callable $referencia, ?int $planoId, ?array $novoPlano): array
    {
        if ($candidatos === []) {
            throw new DomainException('Nenhum caso de teste para importar.');
        }

        return $this->transacao->executar(function () use ($candidatos, $origem, $referencia, $planoId, $novoPlano) {
            $plano = $planoId
                ? ($this->planos->buscarPorId($planoId) ?? throw EntidadeNaoEncontradaException::para('Plano de teste', $planoId))
                : $this->planos->salvar(PlanoTeste::criar(
                    $novoPlano['nome'] ?? throw new DomainException('Informe o plano de destino ou o nome do novo plano.'),
                    $novoPlano['descricao'] ?? null,
                    $novoPlano['projeto'] ?? null,
                    $novoPlano['versao'] ?? null,
                ));

            $plano->garantirEditavel();

            $existentes = array_flip($this->casos->referenciasDoPlano($plano->id()));
            $ordem = $this->casos->proximaOrdem($plano->id());
            $criados = 0;
            $ignorados = 0;

            foreach (array_values($candidatos) as $i => $candidato) {
                $ref = $referencia($candidato, $i);
                if ($ref !== null && isset($existentes[$ref])) {
                    $ignorados++;
                    continue;
                }

                $this->casos->salvar(CasoTeste::criar(
                    planoId: $plano->id(),
                    titulo: $candidato->titulo,
                    passos: $candidato->passos,
                    resultadoEsperado: $candidato->resultadoEsperado,
                    prioridade: $candidato->prioridade,
                    preCondicoes: $candidato->preCondicoes,
                    ordem: $ordem++,
                    origem: $origem,
                    referencia: $ref,
                ));
                $existentes[$ref] = true;
                $criados++;
            }

            return ['plano_id' => $plano->id(), 'criados' => $criados, 'ignorados' => $ignorados];
        });
    }
}
