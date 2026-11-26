<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ConexaoMcpModel extends Model
{
    protected $table = 'conexoes_mcp';

    protected $fillable = [
        'nome', 'url', 'token', 'cabecalhos', 'ferramenta_casos', 'argumentos_padrao', 'ativo',
        'ultimo_teste_em', 'ultimo_teste_ok', 'ultimo_teste_mensagem',
    ];

    protected $casts = [
        'token' => 'encrypted',              // segredo criptografado com a APP_KEY
        'cabecalhos' => 'encrypted:array',
        'argumentos_padrao' => 'array',
        'ativo' => 'boolean',
        'ultimo_teste_ok' => 'boolean',
        'ultimo_teste_em' => 'immutable_datetime',
    ];

    protected $hidden = ['token', 'cabecalhos'];
}
