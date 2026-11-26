<?php

namespace Src\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Domain\Execucao\Enums\ResultadoExecucao;

class RegistrarExecucaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resultado' => ['required', Rule::enum(ResultadoExecucao::class)],
            'observacao' => ['nullable', 'string'],
            'executado_por' => ['nullable', 'string', 'max:100'],
        ];
    }
}
