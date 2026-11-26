<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class ModuloModel extends Model
{
    protected $table = 'modulos';

    protected $fillable = ['nome', 'tag', 'descricao', 'cor'];
}
