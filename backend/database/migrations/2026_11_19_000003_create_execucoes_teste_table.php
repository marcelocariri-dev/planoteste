<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execucoes_teste', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caso_id')->constrained('casos_teste')->cascadeOnDelete();
            $table->string('resultado', 20)->comment('passou | falhou | bloqueado');
            $table->text('observacao')->nullable();
            $table->string('executado_por', 100)->nullable();
            $table->dateTime('executado_em');
            $table->timestamps();

            $table->index(['caso_id', 'executado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execucoes_teste');
    }
};
