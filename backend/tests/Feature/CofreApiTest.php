<?php

namespace Tests\Feature;

use Database\Seeders\CategoriasDocumentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CofreApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(CategoriasDocumentoSeeder::class);
    }

    public function test_upload_avulso_listagem_download_e_exclusao(): void
    {
        $moduloId = $this->postJson('/api/modulos', ['nome' => 'Fiscal'])->json('data.id');
        $categoriaId = $this->getJson('/api/cofre/categorias')->assertOk()->json('data.0.id');

        $doc = $this->post('/api/cofre/documentos', [
            'arquivo' => UploadedFile::fake()->create('NT 2025.001.pdf', 120, 'application/pdf'),
            'categoria_id' => $categoriaId,
            'modulo_id' => $moduloId,
            'tags' => 'nfce, sefaz',
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.titulo', 'NT 2025.001')
            ->assertJsonPath('data.origem', 'avulso')
            ->assertJsonPath('data.tags', ['NFCE', 'SEFAZ'])
            ->json('data');

        $caminho = \Src\Infrastructure\Persistence\Eloquent\Models\DocumentoModel::find($doc['id'])->caminho;
        $this->assertStringStartsWith('cofre/FISCAL/', $caminho);
        Storage::disk('local')->assertExists($caminho);

        $this->getJson("/api/cofre/documentos?modulo_id={$moduloId}")->assertJsonCount(1, 'data');
        $this->getJson('/api/cofre/documentos?busca=sefaz')->assertJsonCount(1, 'data');
        $this->getJson('/api/cofre/documentos?sem_modulo=1')->assertJsonCount(0, 'data');

        $this->get("/api/cofre/documentos/{$doc['id']}/download")->assertOk()->assertDownload('NT 2025.001.pdf');

        $this->deleteJson("/api/cofre/categorias/{$categoriaId}")->assertUnprocessable();
        $this->deleteJson("/api/cofre/documentos/{$doc['id']}")->assertNoContent();
        Storage::disk('local')->assertMissing($caminho);
    }

    public function test_anexo_da_regra_vai_para_o_modulo_da_regra(): void
    {
        $pdv = $this->postJson('/api/modulos', ['nome' => 'PDV'])->json('data.id');
        $outro = $this->postJson('/api/modulos', ['nome' => 'Estoque'])->json('data.id');
        $regraId = $this->postJson('/api/regras', ['modulo_id' => $pdv, 'titulo' => 'R', 'tipo' => 'novo_modulo'])->json('data.id');

        $this->post('/api/cofre/documentos', [
            'arquivo' => UploadedFile::fake()->create('prototipo.png', 10, 'image/png'),
            'categoria_id' => 1,
            'modulo_id' => $outro, // ignorado: vale o módulo da regra
            'regra_id' => $regraId,
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.modulo_id', $pdv)
            ->assertJsonPath('data.origem', 'validacao');

        $this->getJson("/api/regras/{$regraId}")->assertJsonPath('data.total_documentos', 1);
        $this->getJson('/api/modulos')->assertJsonPath('data.1.total_documentos', 1); // PDV (ordem alfabética: Estoque, PDV)
    }

    public function test_upload_sem_arquivo_ou_categoria_invalida(): void
    {
        $this->postJson('/api/cofre/documentos', ['categoria_id' => 1])->assertUnprocessable()->assertJsonValidationErrors('arquivo');
        $this->post('/api/cofre/documentos', [
            'arquivo' => UploadedFile::fake()->create('a.txt', 1), 'categoria_id' => 999,
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonPath('message', 'Selecione uma categoria válida.');
    }
}
