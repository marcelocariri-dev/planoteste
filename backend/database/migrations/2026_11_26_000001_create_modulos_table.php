<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('tag', 40)->unique()->comment('Identificador usado para departamentalizar regras e documentos');
            $table->text('descricao')->nullable();
            $table->string('cor', 7)->default('#2563eb');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
