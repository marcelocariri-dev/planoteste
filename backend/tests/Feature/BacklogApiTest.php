<?php

namespace Tests\Feature;

use Database\Seeders\CategoriasDocumentoSeeder;
use Database\Seeders\PadraoBacklogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BacklogApiTest extends TestCase
{
    use RefreshDatabase;

    private function respostaJson(): string
    {
        return json_encode(['epicos' => [[
            'codigo' => 'EP-01', 'titulo' => 'Cashback', 'descricao' => 'Fidelização',
            'historias' => [
                ['codigo' => 'US-01', 'titulo' => 'Acumular cashback', 'como_um' => 'cliente', 'quero' => 'acumular', 'para' => 'economizar',
                    'criterios_aceite' => ['Dado um cliente cadastrado Quando comprar R$ 100 Então acumula R$ 2'], 'prioridade' => 'Must', 'estimativa' => 5],
                ['codigo' => 'US-02', 'titulo' => 'Usar cashback', 'como_um' => 'cliente', 'quero' => 'usar', 'para' => 'pagar menos',
                    'criterios_aceite' => [], 'prioridade' => 'Could'],
            ],
        ]]]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed([CategoriasDocumentoSeeder::class, PadraoBacklogSeeder::class]);
    }

    public function test_sem_api_gera_prompt_e_aceita_resposta_colada(): void
    {
        config(['services.anthropic.key' => null]);
        $this->getJson('/api/backlog/configuracao')->assertJsonPath('data.ia_disponivel', false);

        $id = $this->postJson('/api/backlogs', [
            'titulo' => 'Cashback', 'padrao_id' => 1, 'orientacoes' => 'Programa de cashback de 2%',
        ])->assertCreated()->assertJsonPath('data.status', 'aguardando_ia')->json('data.id');

        $prompt = $this->getJson("/api/backlogs/{$id}")->json('data.prompt');
        $this->assertStringContainsString('Programa de cashback de 2%', $prompt);
        $this->assertStringContainsString('Formato obrigatório da resposta', $prompt);

        $this->postJson("/api/backlogs/{$id}/resposta", ['resposta' => 'lixo'])->assertUnprocessable();
        $this->postJson("/api/backlogs/{$id}/resposta", ['resposta' => "```json\n{$this->respostaJson()}\n```"])
            ->assertOk()->assertJsonPath('data.status', 'gerado')->assertJsonPath('data.total_historias', 2);

        $planoId = $this->postJson("/api/backlogs/{$id}/gerar-plano", ['historias' => ['US-01']])
            ->assertCreated()->assertJsonPath('data.criados', 1)->json('data.plano_id');

        $this->getJson("/api/planos/{$planoId}")
            ->assertJsonPath('data.casos.0.titulo', 'US-01 - Acumular cashback')
            ->assertJsonPath('data.casos.0.origem', 'backlog')
            ->assertJsonPath('data.casos.0.prioridade', 'alta')
            ->assertJsonPath('data.casos.0.passos', '1. Quando comprar R$ 100')
            ->assertJsonPath('data.casos.0.resultado_esperado', 'Então acumula R$ 2');
    }

    public function test_com_api_do_claude_envia_documentos_e_salva_epicos(): void
    {
        config(['services.anthropic.key' => 'sk-teste', 'services.anthropic.model' => 'claude-teste']);
        Http::fake(['api.anthropic.com/*' => Http::response([
            'model' => 'claude-teste', 'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => $this->respostaJson()]],
        ])]);

        $docId = $this->post('/api/cofre/documentos', [
            'arquivo' => UploadedFile::fake()->createWithContent('regras.txt', 'Cashback de 2% em compras acima de R$ 50'),
            'categoria_id' => 1,
        ], ['Accept' => 'application/json'])->json('data.id');

        $this->postJson('/api/backlogs', [
            'titulo' => 'Cashback', 'padrao_id' => 1, 'orientacoes' => 'Gerar backlog', 'documento_ids' => [$docId],
        ])->assertCreated()->assertJsonPath('data.status', 'gerado')->assertJsonPath('data.modelo', 'claude-teste')
            ->assertJsonPath('data.epicos.0.historias.1.codigo', 'US-02');

        Http::assertSent(function ($request) {
            $conteudo = $request['messages'][0]['content'];

            return $request->hasHeader('x-api-key', 'sk-teste')
                && $conteudo[0]['type'] === 'document'
                && $conteudo[0]['source']['data'] === 'Cashback de 2% em compras acima de R$ 50'
                && str_contains(end($conteudo)['text'], 'Gerar backlog');
        });
    }

    public function test_erro_da_api_fica_registrado_no_backlog(): void
    {
        config(['services.anthropic.key' => 'sk-teste']);
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['message' => 'invalid x-api-key']], 401)]);

        $this->postJson('/api/backlogs', ['titulo' => 'X', 'padrao_id' => 1, 'orientacoes' => 'y'])
            ->assertCreated()->assertJsonPath('data.status', 'erro')
            ->assertJsonPath('data.erro', 'API do Claude respondeu 401: invalid x-api-key');
    }

    public function test_padroes_crud(): void
    {
        $this->postJson('/api/backlog/padroes', ['nome' => 'Kanban', 'instrucoes' => 'curto'])->assertUnprocessable();
        $id = $this->postJson('/api/backlog/padroes', ['nome' => 'Kanban', 'instrucoes' => 'Liste itens para: {{orientacoes}}'])
            ->assertCreated()->json('data.id');
        $this->deleteJson("/api/backlog/padroes/{$id}")->assertNoContent();
        $this->deleteJson('/api/backlog/padroes/1')->assertUnprocessable(); // último padrão
    }
}
