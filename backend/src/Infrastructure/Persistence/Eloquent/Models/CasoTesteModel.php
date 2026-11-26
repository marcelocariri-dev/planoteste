<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CasoTesteModel extends Model
{
    protected $table = 'casos_teste';

    protected $fillable = ['plano_id', 'titulo', 'pre_condicoes', 'passos', 'resultado_esperado', 'prioridade', 'ordem'];

    protected $casts = ['ordem' => 'integer', 'plano_id' => 'integer'];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(PlanoTesteModel::class, 'plano_id');
    }

    public function execucoes(): HasMany
    {
        return $this->hasMany(ExecucaoModel::class, 'caso_id');
    }
}
