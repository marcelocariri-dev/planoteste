<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Application\Modulo\UseCases\GerenciarModulos;
use Src\Application\RegraNegocio\UseCases\GerenciarRegras;
use Src\Infrastructure\Persistence\Eloquent\Models\ModuloModel;

class RegraExemploSeeder extends Seeder
{
    public function run(GerenciarModulos $modulos, GerenciarRegras $regras): void
    {
        if (ModuloModel::exists()) {
            return;
        }

        $pdv = $modulos->salvar(null, ['nome' => 'PDV', 'tag' => 'PDV', 'descricao' => 'Frente de caixa e NFC-e', 'cor' => '#2563eb']);
        $modulos->salvar(null, ['nome' => 'Fiscal', 'tag' => 'FISCAL', 'descricao' => 'NF-e, SPED e obrigações', 'cor' => '#7c3aed']);
        $modulos->salvar(null, ['nome' => 'Estoque', 'tag' => 'ESTOQUE', 'cor' => '#059669']);

        $regras->criar([
            'modulo_id' => $pdv['id'],
            'titulo' => 'Desconto no PDV acima do limite do operador',
            'tipo' => 'melhoria',
            'autor' => 'PO',
            'conteudo' => <<<'MD'
## Contexto
Hoje o operador consegue aplicar qualquer desconto na venda. A loja precisa limitar o desconto por perfil.

## Objetivo
Exigir autorização do gerente quando o desconto ultrapassar o limite configurado para o operador.

::: regra Limite por perfil
O limite de desconto (%) é configurado por perfil de usuário. Padrão: 5%.
:::

::: caso [alta] Desconto dentro do limite
Pré-condições: operador com limite de 5%
Passos:
1. Incluir um produto de R$ 100,00
2. Aplicar desconto de 5%
Resultado esperado: desconto aplicado sem pedir senha
:::

::: caso [critica] Desconto acima do limite exige gerente
Dado um operador com limite de 5%
Quando aplicar 10% de desconto na venda
Então o sistema solicita a senha do gerente
E registra no log quem autorizou
:::

::: excecao
Vendas em contingência offline seguem a mesma regra, validando a senha localmente.
:::

::: duvida
O limite vale por item ou pelo total da venda?
:::
MD,
        ]);
    }
}
