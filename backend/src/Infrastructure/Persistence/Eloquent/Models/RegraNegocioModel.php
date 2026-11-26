<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class RegraNegocioModel extends Model
{
    protected $table = 'regras_negocio';

    protected $fillable = [
        'modulo_id', 'titulo', 'tipo', 'conteudo', 'status', 'versao', 'autor',
        'pm_decisao', 'pm_responsavel', 'pm_parecer', 'pm_em',
        'po_decisao', 'po_responsavel', 'po_parecer', 'po_em',
    ];

    protected $casts = [
        'modulo_id' => 'integer',
        'versao' => 'integer',
        'pm_em' => 'immutable_datetime',
        'po_em' => 'immutable_datetime',
    ];
}
