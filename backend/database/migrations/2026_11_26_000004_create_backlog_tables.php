<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('padroes_backlog', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('descricao', 255)->nullable();
            $table->longText('instrucoes')->comment('Template do prompt com {{modulo}} {{orientacoes}} {{regras}} {{documentos}}');
            $table->timestamps();
        });

        Schema::create('backlogs', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->foreignId('modulo_id')->nullable()->constrained('modulos')->nullOnDelete();
            $table->foreignId('padrao_id')->nullable()->constrained('padroes_backlog')->nullOnDelete();
            $table->longText('orientacoes');
            $table->json('documento_ids')->nullable();
            $table->json('regra_ids')->nullable();
            $table->longText('prompt');
            $table->string('status', 20)->comment('aguardando_ia | gerado | erro');
            $table->json('epicos')->nullable();
            $table->longText('resposta_bruta')->nullable();
            $table->string('modelo', 100)->nullable();
            $table->text('erro')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backlogs');
        Schema::dropIfExists('padroes_backlog');
    }
};
