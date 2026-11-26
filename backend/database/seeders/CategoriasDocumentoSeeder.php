<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Infrastructure\Persistence\Eloquent\Models\CategoriaDocumentoModel;

class CategoriasDocumentoSeeder extends Seeder
{
    public function run(): void
    {
        if (CategoriaDocumentoModel::exists()) {
            return;
        }

        foreach ([
            ['Regra de negócio', 'Documentos de regra, fluxos e protótipos validados por PM/PO'],
            ['Norma técnica', 'Notas técnicas, manuais de integração, leiautes oficiais (SEFAZ, etc.)'],
            ['Legislação', 'Leis, decretos, convênios e ajustes'],
            ['Especificação técnica', 'Documentos de arquitetura, APIs e contratos'],
            ['Manual / Guia', 'Manuais de uso e guias internos'],
            ['Evidência de teste', 'Prints, vídeos, logs e relatórios de execução'],
            ['Outros', null],
        ] as [$nome, $descricao]) {
            CategoriaDocumentoModel::create(['nome' => $nome, 'descricao' => $descricao]);
        }
    }
}
