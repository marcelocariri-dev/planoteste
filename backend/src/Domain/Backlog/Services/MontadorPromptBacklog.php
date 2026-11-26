<?php

namespace Src\Domain\Backlog\Services;

use Src\Domain\Backlog\Entities\PadraoBacklog;

/**
 * Preenche o padrão com as entradas e anexa o contrato de saída (JSON) que o interpretador entende.
 */
final class MontadorPromptBacklog
{
    public const CONTRATO_SAIDA = <<<'TXT'

## Formato obrigatório da resposta
Responda SOMENTE com um JSON válido (sem texto antes ou depois), neste formato:
{
  "epicos": [
    {
      "codigo": "EP-01",
      "titulo": "string",
      "descricao": "string",
      "historias": [
        {
          "codigo": "US-01",
          "titulo": "string",
          "como_um": "persona",
          "quero": "ação/necessidade",
          "para": "benefício de negócio",
          "criterios_aceite": ["Dado ... Quando ... Então ..."],
          "prioridade": "Must | Should | Could | Won't",
          "estimativa": 3,
          "observacoes": "dependências, riscos ou dúvidas (opcional)"
        }
      ]
    }
  ]
}
TXT;

    /**
     * @param array<int, array{titulo: string, modulo: ?string, versao: int, status: string, conteudo: string}> $regras
     * @param string[] $nomesDocumentos
     */
    public function montar(PadraoBacklog $padrao, string $orientacoes, ?string $modulo, array $regras, array $nomesDocumentos): string
    {
        $textoRegras = $regras === []
            ? '(nenhuma regra de negócio selecionada)'
            : implode("\n\n", array_map(
                fn (array $r) => sprintf("### %s (v%d - %s%s)\n%s", $r['titulo'], $r['versao'], $r['status'],
                    $r['modulo'] ? " - módulo {$r['modulo']}" : '', trim($r['conteudo'])),
                $regras,
            ));

        $textoDocs = $nomesDocumentos === []
            ? '(nenhum documento anexado)'
            : "Os documentos abaixo foram enviados em anexo a esta mensagem:\n- " . implode("\n- ", $nomesDocumentos);

        $prompt = strtr($padrao->instrucoes(), [
            '{{modulo}}' => $modulo ?? 'não informado',
            '{{orientacoes}}' => trim($orientacoes),
            '{{regras}}' => $textoRegras,
            '{{documentos}}' => $textoDocs,
        ]);

        return rtrim($prompt) . "\n" . self::CONTRATO_SAIDA;
    }
}
