<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\RegraNegocio\Entities\RegraNegocio;
use Src\Domain\RegraNegocio\Enums\DecisaoValidacao as D;
use Src\Domain\RegraNegocio\Enums\PapelValidador as P;
use Src\Domain\RegraNegocio\Enums\StatusRegra;
use Src\Domain\RegraNegocio\Enums\TipoMarcador;
use Src\Domain\RegraNegocio\Enums\TipoRegra;
use Src\Domain\RegraNegocio\Services\ExtratorMarcadores;
use Src\Domain\Shared\Exceptions\DomainException;

class RegraNegocioTest extends TestCase
{
    private function regra(string $conteudo = 'texto da regra'): RegraNegocio
    {
        return RegraNegocio::criar(1, 'Desconto', TipoRegra::MELHORIA, $conteudo);
    }

    public function test_aprovada_somente_com_pm_e_po(): void
    {
        $r = $this->regra();
        $r->enviarParaValidacao();
        $r->registrarParecer(P::PM, D::APROVADO, 'Ana', null);
        $this->assertSame(StatusRegra::EM_VALIDACAO, $r->status());

        $r->registrarParecer(P::PO, D::APROVADO, 'Bruno', 'ok');
        $this->assertSame(StatusRegra::APROVADA, $r->status());
    }

    public function test_reprovacao_exige_parecer_e_volta_para_rascunho_ao_editar(): void
    {
        $r = $this->regra();
        $r->enviarParaValidacao();

        try {
            $r->registrarParecer(P::PO, D::REPROVADO, 'Bruno', '  ');
            $this->fail('deveria exigir parecer');
        } catch (DomainException) {
        }

        $r->registrarParecer(P::PO, D::REPROVADO, 'Bruno', 'Faltou a exceção offline');
        $this->assertSame(StatusRegra::REPROVADA, $r->status());

        $r->editar(1, 'Desconto', TipoRegra::MELHORIA, 'texto corrigido', null);
        $this->assertSame(StatusRegra::RASCUNHO, $r->status());
        $this->assertSame(2, $r->versao());
        $this->assertSame(D::PENDENTE, $r->validacao(P::PO)->decisao);
    }

    public function test_regra_em_validacao_ou_aprovada_nao_edita(): void
    {
        $r = $this->regra();
        $r->enviarParaValidacao();
        $this->expectException(DomainException::class);
        $r->editar(1, 'x', TipoRegra::MELHORIA, 'y', null);
    }

    public function test_nao_envia_regra_vazia(): void
    {
        $this->expectException(DomainException::class);
        $this->regra('   ')->enviarParaValidacao();
    }

    public function test_editar_sem_mudar_texto_nao_gera_versao(): void
    {
        $r = $this->regra();
        $r->editar(2, 'Desconto', TipoRegra::NOVO_MODULO, 'texto da regra', null);
        $this->assertSame(1, $r->versao());
    }

    public function test_extrator_le_marcadores_e_casos(): void
    {
        $texto = <<<'MD'
        Introdução livre.
        ::: caso [alta] Desconto acima do limite
        Pré-condições: operador comum
        Passos:
        1. Aplicar 10%
        Resultado esperado: pede senha do gerente
        :::
        ::: duvida
        Limite por item ou total?
        :::
        ::: caso
        Dado um produto sem estoque
        Quando vender
        Então bloqueia a venda
        E avisa o operador
        :::
        MD;

        $extrator = new ExtratorMarcadores();
        $marcadores = $extrator->extrair($texto);
        $this->assertCount(3, $marcadores);
        $this->assertSame(TipoMarcador::DUVIDA, $marcadores[1]->tipo);
        $this->assertSame('Limite por item ou total?', $marcadores[1]->titulo);

        [$c1, $c2] = $extrator->casos($texto);
        $this->assertSame('Desconto acima do limite', $c1->titulo);
        $this->assertSame(Prioridade::ALTA, $c1->prioridade);
        $this->assertSame('operador comum', $c1->preCondicoes);
        $this->assertSame('1. Aplicar 10%', $c1->passos);
        $this->assertSame('pede senha do gerente', $c1->resultadoEsperado);

        $this->assertSame('Dado um produto sem estoque', $c2->titulo);
        $this->assertSame('Quando vender', $c2->passos);
        $this->assertSame("Então bloqueia a venda\nE avisa o operador", $c2->resultadoEsperado);
    }

    public function test_bloco_nao_fechado_e_considerado_ate_o_fim(): void
    {
        $casos = (new ExtratorMarcadores())->casos("::: caso Sem fechamento\nPassos: fazer algo");
        $this->assertCount(1, $casos);
        $this->assertSame('fazer algo', $casos[0]->passos);
    }
}
