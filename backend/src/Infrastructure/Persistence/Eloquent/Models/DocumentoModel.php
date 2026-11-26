<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoModel extends Model
{
    protected $table = 'documentos';

    protected $fillable = [
        'titulo', 'descricao', 'categoria_id', 'modulo_id', 'regra_id', 'origem',
        'caminho', 'nome_original', 'mime_type', 'tamanho', 'tags', 'enviado_por',
    ];

    protected $casts = [
        'tags' => 'array',
        'categoria_id' => 'integer',
        'modulo_id' => 'integer',
        'regra_id' => 'integer',
        'tamanho' => 'integer',
    ];
}
