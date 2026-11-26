<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casos_teste', function (Blueprint $table) {
            $table->string('origem', 20)->default('manual')->after('ordem')->comment('manual | regra | mcp | backlog');
            $table->string('referencia', 150)->nullable()->after('origem')->comment('Id na origem (rastreabilidade/deduplicação)');
            $table->index(['plano_id', 'referencia']);
        });
    }

    public function down(): void
    {
        Schema::table('casos_teste', function (Blueprint $table) {
            $table->dropIndex(['plano_id', 'referencia']);
            $table->dropColumn(['origem', 'referencia']);
        });
    }
};
