<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExecucaoModel extends Model
{
    protected $table = 'execucoes_teste';

    protected $fillable = ['caso_id', 'resultado', 'observacao', 'executado_por', 'executado_em'];

    protected $casts = ['executado_em' => 'immutable_datetime', 'caso_id' => 'integer'];

    public function caso(): BelongsTo
    {
        return $this->belongsTo(CasoTesteModel::class, 'caso_id');
    }
}
