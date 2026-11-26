<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanoTesteApiTest extends TestCase
{
    use RefreshDatabase;

    private function criarPlano(): int
    {
        return $this->postJson('/api/planos', ['nome' => 'Regressão', 'projeto' => 'SoftcomShop'])
            ->assertCreated()
            ->json('data.id');
    }

    private function criarCaso(int $planoId): int
    {
        return $this->postJson("/api/planos/{$planoId}/casos", [
            'titulo' => 'Venda à vista',
            'passos' => 'Incluir produto e finalizar',
            'resultado_esperado' => 'Venda concluída',
            'prioridade' => 'alta',
        ])->assertCreated()->json('data.id');
    }

    public function test_crud_de_plano(): void
    {
        $id = $this->criarPlano();

        $this->getJson('/api/planos')->assertOk()->assertJsonPath('data.0.nome', 'Regressão');

        $this->putJson("/api/planos/{$id}", ['nome' => 'Regressão v2'])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Regressão v2');

        $this->deleteJson("/api/planos/{$id}")->assertNoContent();
        $this->getJson("/api/planos/{$id}")->assertNotFound();
    }

    public function test_validacao_de_entrada(): void
    {
        $this->postJson('/api/planos', [])->assertUnprocessable()->assertJsonValidationErrors('nome');
    }

    public function test_execucao_inicia_plano_e_atualiza_resumo(): void
    {
        $planoId = $this->criarPlano();
        $casoId = $this->criarCaso($planoId);
        $this->criarCaso($planoId);

        $this->postJson("/api/casos/{$casoId}/execucoes", ['resultado' => 'falhou', 'observacao' => 'Erro 500'])
            ->assertCreated();
        $this->postJson("/api/casos/{$casoId}/execucoes", ['resultado' => 'passou'])
            ->assertCreated();

        $this->getJson("/api/planos/{$planoId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'em_execucao')
            ->assertJsonPath('data.resumo.total', 2)
            ->assertJsonPath('data.resumo.passou', 1)
            ->assertJsonPath('data.resumo.falhou', 0)
            ->assertJsonPath('data.resumo.nao_executado', 1)
            ->assertJsonPath('data.casos.0.resultado_atual', 'passou');

        $this->getJson("/api/casos/{$casoId}/execucoes")->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_falha_sem_observacao_retorna_422(): void
    {
        $casoId = $this->criarCaso($this->criarPlano());

        $this->postJson("/api/casos/{$casoId}/execucoes", ['resultado' => 'falhou'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Informe uma observação quando o resultado for "Falhou".');
    }

    public function test_plano_concluido_bloqueia_alteracoes(): void
    {
        $planoId = $this->criarPlano();
        $casoId = $this->criarCaso($planoId);

        $this->postJson("/api/planos/{$planoId}/concluir")->assertOk()->assertJsonPath('data.status', 'concluido');

        $this->postJson("/api/casos/{$casoId}/execucoes", ['resultado' => 'passou'])->assertUnprocessable();
        $this->deleteJson("/api/casos/{$casoId}")->assertUnprocessable();

        $this->postJson("/api/planos/{$planoId}/reabrir")->assertOk()->assertJsonPath('data.status', 'em_execucao');
        $this->deleteJson("/api/casos/{$casoId}")->assertNoContent();
    }
}
