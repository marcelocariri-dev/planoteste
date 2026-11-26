<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class BacklogModel extends Model
{
    protected $table = 'backlogs';

    protected $fillable = [
        'titulo', 'modulo_id', 'padrao_id', 'orientacoes', 'documento_ids', 'regra_ids', 'prompt',
        'status', 'epicos', 'resposta_bruta', 'modelo', 'erro',
    ];

    protected $casts = [
        'documento_ids' => 'array',
        'regra_ids' => 'array',
        'epicos' => 'array',
        'modulo_id' => 'integer',
        'padrao_id' => 'integer',
    ];
}
