<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conexoes_mcp', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('url', 500);
            $table->text('token')->nullable()->comment('Criptografado (APP_KEY)');
            $table->text('cabecalhos')->nullable()->comment('JSON criptografado');
            $table->string('ferramenta_casos', 150)->nullable();
            $table->json('argumentos_padrao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->dateTime('ultimo_teste_em')->nullable();
            $table->boolean('ultimo_teste_ok')->nullable();
            $table->string('ultimo_teste_mensagem', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conexoes_mcp');
    }
};
