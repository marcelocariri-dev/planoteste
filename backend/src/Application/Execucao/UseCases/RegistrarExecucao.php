<?php

namespace Src\Application\Execucao\UseCases;

use Src\Application\Execucao\DTOs\RegistrarExecucaoDados;
use Src\Application\Shared\TransactionManagerInterface;
use Src\Domain\CasoTeste\Repositories\CasoTesteRepositoryInterface;
use Src\Domain\Execucao\Entities\Execucao;
use Src\Domain\Execucao\Repositories\ExecucaoRepositoryInterface;
use Src\Domain\PlanoTeste\Enums\StatusPlano;
use Src\Domain\PlanoTeste\Repositories\PlanoTesteRepositoryInterface;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

final class RegistrarExecucao
{
    public function __construct(
        private readonly PlanoTesteRepositoryInterface $planos,
        private readonly CasoTesteRepositoryInterface $casos,
        private readonly ExecucaoRepositoryInterface $execucoes,
        private readonly TransactionManagerInterface $transacao,
    ) {
    }

    public function executar(int $casoId, RegistrarExecucaoDados $dados): array
    {
        $caso = $this->casos->buscarPorId($casoId)
            ?? throw EntidadeNaoEncontradaException::para('Caso de teste', $casoId);

        $plano = $this->planos->buscarPorId($caso->planoId())
            ?? throw EntidadeNaoEncontradaException::para('Plano de teste', $caso->planoId());

        // valida a execução antes de qualquer alteração de estado
        $execucao = Execucao::registrar($casoId, $dados->resultado, $dados->observacao, $dados->executadoPor);

        $statusAnterior = $plano->status();
        $plano->iniciarExecucao(); // lança exceção se o plano estiver concluído

        return $this->transacao->executar(function () use ($plano, $statusAnterior, $execucao) {
            if ($statusAnterior === StatusPlano::RASCUNHO) {
                $this->planos->salvar($plano);
            }

            return $this->execucoes->salvar($execucao)->toArray();
        });
    }
}
