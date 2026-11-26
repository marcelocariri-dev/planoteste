<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class PadraoBacklogModel extends Model
{
    protected $table = 'padroes_backlog';

    protected $fillable = ['nome', 'descricao', 'instrucoes'];
}
