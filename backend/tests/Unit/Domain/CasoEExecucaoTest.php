<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\CasoTeste\Entities\CasoTeste;
use Src\Domain\Execucao\Entities\Execucao;
use Src\Domain\Execucao\Enums\ResultadoExecucao;
use Src\Domain\PlanoTeste\ValueObjects\ResumoExecucao;
use Src\Domain\Shared\Exceptions\DomainException;

class CasoEExecucaoTest extends TestCase
{
    public function test_caso_exige_passos_e_resultado_esperado(): void
    {
        $this->expectException(DomainException::class);
        CasoTeste::criar(1, 'Login', '', 'Entra no sistema');
    }

    public function test_caso_sem_execucao_aparece_como_nao_executado(): void
    {
        $caso = CasoTeste::criar(1, 'Login', 'Informar usuário e senha', 'Entra no sistema');

        $this->assertSame('nao_executado', $caso->toArray()['resultado_atual']);
    }

    public function test_falha_exige_observacao(): void
    {
        $this->expectException(DomainException::class);
        Execucao::registrar(1, ResultadoExecucao::FALHOU, '  ');
    }

    public function test_passou_nao_exige_observacao(): void
    {
        $execucao = Execucao::registrar(1, ResultadoExecucao::PASSOU);

        $this->assertNull($execucao->observacao());
    }

    public function test_resumo_calcula_percentuais(): void
    {
        $resumo = ResumoExecucao::apartirDe([
            ResultadoExecucao::PASSOU,
            ResultadoExecucao::PASSOU,
            ResultadoExecucao::FALHOU,
            null,
        ]);

        $this->assertSame(4, $resumo->total);
        $this->assertSame(1, $resumo->naoExecutado);
        $this->assertSame(75.0, $resumo->percentualExecutado());
        $this->assertSame(66.7, $resumo->percentualSucesso());
    }

    public function test_resumo_vazio(): void
    {
        $resumo = ResumoExecucao::apartirDe([]);

        $this->assertSame(0.0, $resumo->percentualExecutado());
        $this->assertSame(0.0, $resumo->percentualSucesso());
    }
}
