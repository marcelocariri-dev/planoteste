<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_documento', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80)->unique();
            $table->string('descricao', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200);
            $table->text('descricao')->nullable();
            $table->foreignId('categoria_id')->constrained('categorias_documento')->restrictOnDelete();
            $table->foreignId('modulo_id')->nullable()->constrained('modulos')->restrictOnDelete();
            $table->foreignId('regra_id')->nullable()->constrained('regras_negocio')->nullOnDelete();
            $table->string('origem', 20)->default('avulso')->comment('avulso | validacao');
            $table->string('caminho', 500);
            $table->string('nome_original', 255);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('tamanho');
            $table->json('tags')->nullable();
            $table->string('enviado_por', 100)->nullable();
            $table->timestamps();

            $table->index(['modulo_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
        Schema::dropIfExists('categorias_documento');
    }
};
