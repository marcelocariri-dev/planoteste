<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegraNegocioApiTest extends TestCase
{
    use RefreshDatabase;

    private const CONTEUDO = "## Regra\n::: caso [alta] Caso A\nPassos: fazer A\nResultado esperado: ok A\n:::\n::: caso Caso B\nPassos: fazer B\n:::\n::: duvida\nE se falhar?\n:::";

    private function moduloId(): int
    {
        return $this->postJson('/api/modulos', ['nome' => 'Ponto de Venda', 'cor' => '#ff0000'])->assertCreated()
            ->assertJsonPath('data.tag', 'PONTO-DE-VENDA')->json('data.id');
    }

    private function criarRegra(int $moduloId): int
    {
        return $this->postJson('/api/regras', [
            'modulo_id' => $moduloId, 'titulo' => 'Desconto', 'tipo' => 'melhoria', 'conteudo' => self::CONTEUDO, 'autor' => 'PO',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'rascunho')
            ->assertJsonPath('data.contagem_marcadores.caso', 2)
            ->assertJsonPath('data.contagem_marcadores.duvida', 1)
            ->json('data.id');
    }

    public function test_fluxo_completo_de_validacao_pm_po(): void
    {
        $id = $this->criarRegra($this->moduloId());

        $this->postJson("/api/regras/{$id}/fluxo/enviar")->assertOk()->assertJsonPath('data.status', 'em_validacao');
        $this->putJson("/api/regras/{$id}", ['modulo_id' => 1, 'titulo' => 'x', 'tipo' => 'melhoria', 'conteudo' => 'y'])
            ->assertUnprocessable();

        $this->postJson("/api/regras/{$id}/parecer", ['papel' => 'pm', 'decisao' => 'aprovado', 'responsavel' => 'Ana'])
            ->assertOk()->assertJsonPath('data.status', 'em_validacao');
        $this->postJson("/api/regras/{$id}/parecer", ['papel' => 'po', 'decisao' => 'reprovado', 'responsavel' => 'Bruno'])
            ->assertUnprocessable()->assertJsonPath('message', 'Descreva o motivo da reprovação no parecer.');
        $this->postJson("/api/regras/{$id}/parecer", ['papel' => 'po', 'decisao' => 'reprovado', 'responsavel' => 'Bruno', 'parecer' => 'Falta exceção'])
            ->assertOk()->assertJsonPath('data.status', 'reprovada');

        $this->putJson("/api/regras/{$id}", ['modulo_id' => 1, 'titulo' => 'Desconto', 'tipo' => 'melhoria', 'conteudo' => self::CONTEUDO . "\nnova linha"])
            ->assertOk()->assertJsonPath('data.status', 'rascunho')->assertJsonPath('data.versao', 2);

        $this->getJson("/api/regras/{$id}/versoes")->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.versao', 2);

        $this->postJson("/api/regras/{$id}/fluxo/enviar")->assertOk();
        $this->postJson("/api/regras/{$id}/parecer", ['papel' => 'pm', 'decisao' => 'aprovado', 'responsavel' => 'Ana']);
        $this->postJson("/api/regras/{$id}/parecer", ['papel' => 'po', 'decisao' => 'aprovado', 'responsavel' => 'Bruno'])
            ->assertJsonPath('data.status', 'aprovada');
        $this->deleteJson("/api/regras/{$id}")->assertUnprocessable();
        $this->postJson("/api/regras/{$id}/fluxo/reabrir")->assertOk()->assertJsonPath('data.status', 'rascunho');
    }

    public function test_analisar_sem_salvar(): void
    {
        $this->postJson('/api/regras/analisar', ['conteudo' => self::CONTEUDO])
            ->assertOk()
            ->assertJsonCount(3, 'data.marcadores')
            ->assertJsonPath('data.casos_extraidos.1.resultado_esperado', 'Comportamento conforme descrito na regra de negócio.');
    }

    public function test_gera_plano_dos_casos_marcados_sem_duplicar(): void
    {
        $id = $this->criarRegra($this->moduloId());

        $planoId = $this->postJson("/api/regras/{$id}/gerar-plano")->assertCreated()
            ->assertJsonPath('data.criados', 2)->json('data.plano_id');

        $this->getJson("/api/planos/{$planoId}")->assertOk()
            ->assertJsonPath('data.nome', 'Regra: Desconto')
            ->assertJsonPath('data.projeto', 'Ponto de Venda')
            ->assertJsonPath('data.casos.0.origem', 'regra')
            ->assertJsonPath('data.casos.0.prioridade', 'alta');

        $this->postJson("/api/regras/{$id}/gerar-plano", ['plano_id' => $planoId])->assertCreated()
            ->assertJsonPath('data.criados', 0)->assertJsonPath('data.ignorados', 2);
    }

    public function test_modulo_com_regras_nao_pode_ser_excluido(): void
    {
        $moduloId = $this->moduloId();
        $this->criarRegra($moduloId);
        $this->getJson('/api/modulos')->assertJsonPath('data.0.total_regras', 1);
        $this->deleteJson("/api/modulos/{$moduloId}")->assertUnprocessable();
        $this->postJson('/api/modulos', ['nome' => 'ponto de venda'])->assertUnprocessable();
    }
}
