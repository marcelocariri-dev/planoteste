<?php

namespace Src\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Domain\CasoTeste\Enums\Prioridade;

class CasoTesteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'pre_condicoes' => ['nullable', 'string'],
            'passos' => ['required', 'string'],
            'resultado_esperado' => ['required', 'string'],
            'prioridade' => ['required', Rule::enum(Prioridade::class)],
            'ordem' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
