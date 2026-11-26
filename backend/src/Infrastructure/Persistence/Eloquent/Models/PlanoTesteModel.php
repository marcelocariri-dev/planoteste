<?php

namespace Src\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanoTesteModel extends Model
{
    protected $table = 'planos_teste';

    protected $fillable = ['nome', 'descricao', 'projeto', 'versao', 'responsavel', 'status'];

    public function casos(): HasMany
    {
        return $this->hasMany(CasoTesteModel::class, 'plano_id');
    }
}
