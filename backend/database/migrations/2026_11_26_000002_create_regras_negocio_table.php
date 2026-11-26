<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regras_negocio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulo_id')->constrained('modulos')->restrictOnDelete();
            $table->string('titulo', 200);
            $table->string('tipo', 20)->comment('melhoria | novo_modulo');
            $table->longText('conteudo')->comment('Markdown com marcadores ::: caso|regra|criterio|excecao|duvida');
            $table->string('status', 20)->default('rascunho')->comment('rascunho | em_validacao | aprovada | reprovada');
            $table->unsignedInteger('versao')->default(1);
            $table->string('autor', 100)->nullable();

            foreach (['pm', 'po'] as $papel) {
                $table->string("{$papel}_decisao", 10)->default('pendente');
                $table->string("{$papel}_responsavel", 100)->nullable();
                $table->text("{$papel}_parecer")->nullable();
                $table->dateTime("{$papel}_em")->nullable();
            }

            $table->timestamps();
            $table->index(['modulo_id', 'status']);
        });

        Schema::create('regras_negocio_versoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regra_id')->constrained('regras_negocio')->cascadeOnDelete();
            $table->unsignedInteger('versao');
            $table->string('titulo', 200);
            $table->longText('conteudo');
            $table->string('autor', 100)->nullable();
            $table->timestamp('criado_em')->useCurrent();

            $table->unique(['regra_id', 'versao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regras_negocio_versoes');
        Schema::dropIfExists('regras_negocio');
    }
};
