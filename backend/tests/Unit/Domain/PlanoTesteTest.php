<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\PlanoTeste\Entities\PlanoTeste;
use Src\Domain\PlanoTeste\Enums\StatusPlano;
use Src\Domain\Shared\Exceptions\DomainException;

class PlanoTesteTest extends TestCase
{
    public function test_novo_plano_comeca_como_rascunho(): void
    {
        $plano = PlanoTeste::criar('  Regressão  ');

        $this->assertSame('Regressão', $plano->nome());
        $this->assertSame(StatusPlano::RASCUNHO, $plano->status());
    }

    public function test_nome_obrigatorio(): void
    {
        $this->expectException(DomainException::class);
        PlanoTeste::criar('   ');
    }

    public function test_fluxo_de_status(): void
    {
        $plano = PlanoTeste::criar('Plano');
        $plano->iniciarExecucao();
        $this->assertSame(StatusPlano::EM_EXECUCAO, $plano->status());

        $plano->concluir();
        $this->assertSame(StatusPlano::CONCLUIDO, $plano->status());

        $plano->reabrir();
        $this->assertSame(StatusPlano::EM_EXECUCAO, $plano->status());
    }

    public function test_plano_concluido_nao_pode_ser_editado(): void
    {
        $plano = PlanoTeste::criar('Plano');
        $plano->concluir();

        $this->expectException(DomainException::class);
        $plano->atualizar('Outro', null, null, null, null);
    }

    public function test_so_reabre_plano_concluido(): void
    {
        $this->expectException(DomainException::class);
        PlanoTeste::criar('Plano')->reabrir();
    }
}
