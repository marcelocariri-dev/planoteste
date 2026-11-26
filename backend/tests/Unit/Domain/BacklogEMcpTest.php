<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\Backlog\Entities\PadraoBacklog;
use Src\Domain\Backlog\Services\InterpretadorRespostaBacklog;
use Src\Domain\Backlog\Services\MontadorPromptBacklog;
use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\IntegracaoMcp\Entities\ConexaoMcp;
use Src\Domain\IntegracaoMcp\Services\MapeadorCasosExternos;
use Src\Domain\IntegracaoMcp\ValueObjects\ResultadoFerramenta;
use Src\Domain\Shared\Exceptions\DomainException;

class BacklogEMcpTest extends TestCase
{
    public function test_montador_substitui_variaveis_e_anexa_contrato(): void
    {
        $padrao = PadraoBacklog::criar('Scrum', "Módulo {{modulo}}\n{{orientacoes}}\n{{regras}}\n{{documentos}}");
        $prompt = (new MontadorPromptBacklog())->montar($padrao, 'Criar cashback', 'PDV',
            [['titulo' => 'Regra A', 'modulo' => 'PDV', 'versao' => 2, 'status' => 'Aprovada', 'conteudo' => 'texto']], ['manual.pdf']);

        $this->assertStringContainsString('Módulo PDV', $prompt);
        $this->assertStringContainsString('Criar cashback', $prompt);
        $this->assertStringContainsString('### Regra A (v2 - Aprovada - módulo PDV)', $prompt);
        $this->assertStringContainsString('- manual.pdf', $prompt);
        $this->assertStringContainsString('"epicos"', $prompt);
    }

    public function test_padrao_exige_variavel_orientacoes(): void
    {
        $this->expectException(DomainException::class);
        PadraoBacklog::criar('Sem variavel', 'Gere um backlog completo para o módulo.');
    }

    public function test_interpretador_aceita_bloco_markdown_e_campos_em_ingles(): void
    {
        $resposta = "Segue:\n```json\n" . json_encode(['epics' => [[
            'title' => 'Cashback',
            'stories' => [['title' => 'Acumular', 'as_a' => 'cliente', 'i_want' => 'acumular', 'so_that' => 'economizar',
                'acceptance_criteria' => ['Dado X Quando Y Então Z'], 'priority' => 'Must', 'story_points' => 5]],
        ]]]) . "\n```";

        $epicos = (new InterpretadorRespostaBacklog())->interpretar($resposta);

        $this->assertSame('EP-01', $epicos[0]->codigo);
        $h = $epicos[0]->historias[0];
        $this->assertSame('US-01', $h->codigo);
        $this->assertSame('cliente', $h->comoUm);
        $this->assertSame(5, $h->estimativa);
        $this->assertSame(['Dado X Quando Y Então Z'], $h->criteriosAceite);
    }

    public function test_interpretador_rejeita_texto_sem_json(): void
    {
        $this->expectException(DomainException::class);
        (new InterpretadorRespostaBacklog())->interpretar('não consegui gerar');
    }

    public function test_mapeador_mcp_aceita_varios_formatos(): void
    {
        $mapeador = new MapeadorCasosExternos();

        $estruturado = new ResultadoFerramenta([], ['test_cases' => [[
            'key' => 'QA-10', 'title' => 'Login', 'steps' => [['action' => 'Abrir tela'], 'Informar senha'],
            'expected_result' => 'Entra', 'priority' => 'High', 'preconditions' => 'Usuário ativo',
        ]]], false);
        $c = $mapeador->mapear($estruturado)[0];
        $this->assertSame('QA-10', $c->idExterno);
        $this->assertSame("1. Abrir tela\n2. Informar senha", $c->passos);
        $this->assertSame(Prioridade::ALTA, $c->prioridade);
        $this->assertSame('Usuário ativo', $c->preCondicoes);

        $texto = new ResultadoFerramenta([json_encode([['id' => 7, 'titulo' => 'Venda', 'passos' => 'vender', 'resultado_esperado' => 'ok']])], null, false);
        $this->assertSame('7', $mapeador->mapear($texto)[0]->idExterno);
    }

    public function test_mapeador_mcp_propaga_erro_da_ferramenta(): void
    {
        $this->expectException(DomainException::class);
        (new MapeadorCasosExternos())->mapear(new ResultadoFerramenta(['projeto inexistente'], null, true));
    }

    public function test_conexao_mcp_valida_url_e_nao_expoe_token(): void
    {
        $c = ConexaoMcp::criar('QA oficial', 'https://mcp.empresa.com/mcp', 'segredo');
        $this->assertTrue($c->toArray()['possui_token']);
        $this->assertArrayNotHasKey('token', $c->toArray());

        $this->expectException(DomainException::class);
        ConexaoMcp::criar('X', 'ftp://errado');
    }
}
