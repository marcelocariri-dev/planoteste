<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Infrastructure\Persistence\Eloquent\Models\PadraoBacklogModel;

class PadraoBacklogSeeder extends Seeder
{
    public const INSTRUCOES = <<<'TXT'
Você vai montar o PRODUCT BACKLOG do módulo {{modulo}} seguindo o padrão Scrum da equipe.

## Orientações do PM/PO
{{orientacoes}}

## Regras de negócio validadas
{{regras}}

## Documentos de apoio
{{documentos}}

## Como montar o backlog
1. Agrupe o trabalho em ÉPICOS (códigos EP-01, EP-02...), cada um com uma descrição do objetivo de negócio.
2. Quebre cada épico em HISTÓRIAS DE USUÁRIO (US-01, US-02... numeração contínua) no formato
   "Como <persona>, quero <ação>, para <benefício>". Use personas reais do negócio (operador de caixa, gerente, contador...).
3. Cada história deve seguir INVEST (independente, negociável, valiosa, estimável, pequena, testável).
4. Escreva de 2 a 6 CRITÉRIOS DE ACEITE por história em Gherkin, em português: "Dado ... Quando ... Então ...".
   Cubra o caminho feliz, validações e exceções descritas nas regras/documentos.
5. Prioridade em MoSCoW (Must, Should, Could, Won't) e estimativa em story points (Fibonacci: 1, 2, 3, 5, 8, 13).
6. Em "observacoes", registre dependências, riscos, dúvidas em aberto e pontos de legislação/norma técnica.
7. Não invente requisitos que contradigam as regras; quando faltar informação, registre como dúvida em "observacoes".
TXT;

    public function run(): void
    {
        if (PadraoBacklogModel::exists()) {
            return;
        }

        PadraoBacklogModel::create([
            'nome' => 'Scrum - Épicos, histórias e Gherkin',
            'descricao' => 'Padrão da equipe: épicos > histórias (Como/Quero/Para) > critérios de aceite em Gherkin, MoSCoW e story points.',
            'instrucoes' => self::INSTRUCOES,
        ]);
    }
}
