<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Src\Infrastructure\Persistence\Eloquent\Models\ConexaoMcpModel;
use Tests\TestCase;

class IntegracaoMcpApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'https://mcp.empresa.test/mcp';

    /** Servidor MCP falso: responde initialize em JSON e o resto via SSE, como servidores reais costumam fazer. */
    private function fakeServidor(): void
    {
        Http::fake([self::URL => function (Request $r) {
            if ($r->method() === 'DELETE') {
                return Http::response('', 200);
            }
            $body = json_decode($r->body(), true);
            $id = $body['id'] ?? null;

            return match ($body['method']) {
                'initialize' => Http::response(['jsonrpc' => '2.0', 'id' => $id, 'result' => [
                    'protocolVersion' => '2025-06-18', 'capabilities' => ['tools' => (object) []],
                    'serverInfo' => ['name' => 'qa-oficial', 'version' => '2.1'],
                ]], 200, ['Mcp-Session-Id' => 'sessao-123']),
                'notifications/initialized' => Http::response('', 202),
                'tools/list' => $this->sse($id, ['tools' => [
                    ['name' => 'listar_casos', 'description' => 'Casos oficiais por módulo', 'inputSchema' => ['type' => 'object', 'properties' => ['modulo' => ['type' => 'string']]]],
                ]]),
                'tools/call' => $this->sse($id, [
                    'content' => [['type' => 'text', 'text' => json_encode(['casos' => [
                        ['id' => 'CT-1', 'titulo' => 'Venda simples', 'passos' => ['Abrir caixa', 'Vender'], 'resultado_esperado' => 'NFC-e autorizada', 'prioridade' => 'alta'],
                        ['id' => 'CT-2', 'titulo' => 'Cancelamento', 'passos' => 'Cancelar', 'resultado_esperado' => 'Cancelada'],
                    ]])]],
                    'isError' => false,
                ]),
            };
        }]);
    }

    private function sse(?int $id, array $result)
    {
        $evento = "event: message\ndata: " . json_encode(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]) . "\n\n";

        return Http::response($evento, 200, ['Content-Type' => 'text/event-stream']);
    }

    private function criarConexao(): int
    {
        return $this->postJson('/api/mcp/conexoes', [
            'nome' => 'QA oficial', 'url' => self::URL, 'token' => 'tok-secreto',
            'cabecalhos' => '{"X-Tenant": "softcom"}', 'ferramenta_casos' => 'listar_casos', 'argumentos_padrao' => ['modulo' => 'PDV'],
        ])->assertCreated()->assertJsonPath('data.possui_token', true)->assertJsonMissingPath('data.token')->json('data.id');
    }

    public function test_token_fica_criptografado_no_banco(): void
    {
        $id = $this->criarConexao();
        $bruto = \DB::table('conexoes_mcp')->where('id', $id)->value('token');
        $this->assertNotSame('tok-secreto', $bruto);
        $this->assertSame('tok-secreto', ConexaoMcpModel::find($id)->token);

        // PUT sem token mantém o token atual
        $this->putJson("/api/mcp/conexoes/{$id}", ['nome' => 'QA', 'url' => self::URL])->assertOk()->assertJsonPath('data.possui_token', true);
    }

    public function test_testar_buscar_e_importar_casos_oficiais(): void
    {
        $this->fakeServidor();
        $id = $this->criarConexao();

        $this->postJson("/api/mcp/conexoes/{$id}/testar")->assertOk()
            ->assertJsonPath('data.servidor.nome', 'qa-oficial')
            ->assertJsonPath('data.ferramentas.0.nome', 'listar_casos')
            ->assertJsonPath('data.conexao.ultimo_teste_ok', true);

        $casos = $this->postJson("/api/mcp/conexoes/{$id}/casos")->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.casos.0.id_externo', 'CT-1')
            ->assertJsonPath('data.casos.0.passos', "1. Abrir caixa\n2. Vender")
            ->json('data.casos');

        Http::assertSent(fn (Request $r) => ($r->data()['method'] ?? '') === 'tools/call'
            && json_decode($r->body(), true)['params']['arguments']['modulo'] === 'PDV'
            && $r->hasHeader('Authorization', 'Bearer tok-secreto')
            && $r->hasHeader('X-Tenant', 'softcom')
            && $r->hasHeader('Mcp-Session-Id', 'sessao-123'));

        $planoId = $this->postJson("/api/mcp/conexoes/{$id}/importar", ['casos' => $casos, 'novo_plano' => ['nome' => 'Regressão oficial']])
            ->assertCreated()->assertJsonPath('data.criados', 2)->json('data.plano_id');

        $this->postJson("/api/mcp/conexoes/{$id}/importar", ['casos' => $casos, 'plano_id' => $planoId])
            ->assertCreated()->assertJsonPath('data.criados', 0)->assertJsonPath('data.ignorados', 2);

        $this->getJson("/api/planos/{$planoId}")
            ->assertJsonPath('data.casos.0.origem', 'mcp')
            ->assertJsonPath('data.casos.0.referencia', "mcp:{$id}:CT-1");
    }

    public function test_falha_de_conexao_vira_mensagem_e_fica_registrada(): void
    {
        Http::fake([self::URL => Http::response('unauthorized', 401)]);
        $id = $this->criarConexao();

        $this->postJson("/api/mcp/conexoes/{$id}/testar")->assertUnprocessable()
            ->assertJsonPath('message', 'Falha ao conectar no servidor MCP: acesso negado (HTTP 401). Verifique o token/cabeçalhos.');
        $this->getJson('/api/mcp/conexoes')->assertJsonPath('data.0.ultimo_teste_ok', false);
    }
}
