<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Application\CasoTeste\DTOs\CasoTesteDados;
use Src\Application\CasoTeste\UseCases\AdicionarCasoTeste;
use Src\Application\Execucao\DTOs\RegistrarExecucaoDados;
use Src\Application\Execucao\UseCases\RegistrarExecucao;
use Src\Application\PlanoTeste\DTOs\PlanoTesteDados;
use Src\Application\PlanoTeste\UseCases\CriarPlanoTeste;
use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\Execucao\Enums\ResultadoExecucao;
use Src\Infrastructure\Persistence\Eloquent\Models\PlanoTesteModel;

/**
 * Dados de exemplo criados pelos próprios casos de uso (respeitando as regras do domínio).
 */
class DatabaseSeeder extends Seeder
{
    public function run(
        CriarPlanoTeste $criarPlano,
        AdicionarCasoTeste $adicionarCaso,
        RegistrarExecucao $registrarExecucao,
    ): void {
        if (PlanoTesteModel::exists()) {
            return; // não duplica ao subir o container de novo
        }

        $plano = $criarPlano->executar(new PlanoTesteDados(
            nome: 'Regressão - Emissão de NFC-e',
            descricao: 'Validação do fluxo de venda e emissão de NFC-e no PDV.',
            projeto: 'SoftcomShop',
            versao: '1.0.0',
            responsavel: 'QA',
        ));

        $casos = [
            ['Venda à vista em dinheiro', "1. Abrir o PDV\n2. Incluir um produto\n3. Finalizar em dinheiro", 'NFC-e autorizada e cupom impresso.', Prioridade::CRITICA],
            ['Venda com cartão de crédito', "1. Incluir produto\n2. Finalizar com cartão (TEF)", 'Transação aprovada e NFC-e autorizada.', Prioridade::ALTA],
            ['Cancelamento de NFC-e', "1. Localizar NFC-e emitida\n2. Cancelar informando justificativa", 'Evento de cancelamento homologado.', Prioridade::ALTA],
            ['Emissão em contingência offline', "1. Desconectar a internet\n2. Emitir venda", 'NFC-e emitida em contingência e transmitida depois.', Prioridade::MEDIA],
        ];

        $ids = [];
        foreach ($casos as [$titulo, $passos, $esperado, $prioridade]) {
            $ids[] = $adicionarCaso->executar($plano['id'], new CasoTesteDados(
                titulo: $titulo,
                passos: $passos,
                resultadoEsperado: $esperado,
                prioridade: $prioridade,
                preCondicoes: 'Caixa aberto e certificado digital válido.',
            ))['id'];
        }

        $registrarExecucao->executar($ids[0], new RegistrarExecucaoDados(ResultadoExecucao::PASSOU, executadoPor: 'QA'));
        $registrarExecucao->executar($ids[1], new RegistrarExecucaoDados(ResultadoExecucao::FALHOU, 'TEF retornou timeout após 30s.', 'QA'));
    }
}
