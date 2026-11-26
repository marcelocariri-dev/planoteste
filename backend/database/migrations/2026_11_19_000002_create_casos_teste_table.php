<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casos_teste', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plano_id')->constrained('planos_teste')->cascadeOnDelete();
            $table->string('titulo', 200);
            $table->text('pre_condicoes')->nullable();
            $table->text('passos');
            $table->text('resultado_esperado');
            $table->string('prioridade', 10)->default('media')->comment('baixa | media | alta | critica');
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->index(['plano_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casos_teste');
    }
};
