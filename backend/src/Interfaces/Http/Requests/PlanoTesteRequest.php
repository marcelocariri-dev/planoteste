<?php

namespace Src\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanoTesteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'descricao' => ['nullable', 'string'],
            'projeto' => ['nullable', 'string', 'max:100'],
            'versao' => ['nullable', 'string', 'max:50'],
            'responsavel' => ['nullable', 'string', 'max:100'],
        ];
    }
}
