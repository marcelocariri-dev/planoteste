<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaDocumentoModel extends Model
{
    protected $table = 'categorias_documento';

    protected $fillable = ['nome', 'descricao'];
}
