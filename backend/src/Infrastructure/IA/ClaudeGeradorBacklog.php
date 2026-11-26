<?php

namespace Src\Infrastructure\IA;

use Illuminate\Support\Facades\Http;
use Src\Application\Backlog\Ports\AnexoIA;
use Src\Application\Backlog\Ports\GeradorBacklogInterface;
use Src\Application\Backlog\Ports\RespostaIA;
use Src\Domain\Shared\Exceptions\DomainException;

/**
 * Gera o backlog pela Messages API do Claude.
 * PDFs e imagens vão como blocos nativos; .docx é convertido para texto; texto puro vai como documento de texto.
 */
final class ClaudeGeradorBacklog implements GeradorBacklogInterface
{
    private const URL = 'https://api.anthropic.com/v1/messages';

    private const IMAGENS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const TEXTO_EXT = ['txt', 'md', 'csv', 'json', 'xml', 'html', 'htm', 'yml', 'yaml', 'sql', 'log', 'feature'];

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $modelo,
        private readonly int $maxTokens,
    ) {
    }

    public function disponivel(): bool
    {
        return ! empty($this->apiKey);
    }

    public function gerar(string $prompt, array $anexos): RespostaIA
    {
        if (! $this->disponivel()) {
            throw new DomainException('ANTHROPIC_API_KEY não configurada.');
        }

        $conteudo = [];
        $avisos = [];
        foreach ($anexos as $anexo) {
            $bloco = $this->bloco($anexo);
            $bloco ? $conteudo[] = $bloco : $avisos[] = "Anexo ignorado (formato não suportado): {$anexo->nome}";
        }
        $conteudo[] = ['type' => 'text', 'text' => $prompt];

        $resposta = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(600)->acceptJson()->post(self::URL, [
            'model' => $this->modelo,
            'max_tokens' => $this->maxTokens,
            'system' => 'Você é um Product Owner sênior. Gere product backlogs claros, testáveis e no formato JSON solicitado.',
            'messages' => [['role' => 'user', 'content' => $conteudo]],
        ]);

        if ($resposta->failed()) {
            throw new DomainException(sprintf('API do Claude respondeu %d: %s', $resposta->status(),
                $resposta->json('error.message') ?? mb_substr($resposta->body(), 0, 300)));
        }

        $texto = collect($resposta->json('content', []))->where('type', 'text')->pluck('text')->implode("\n");

        if ($resposta->json('stop_reason') === 'max_tokens') {
            $avisos[] = 'A resposta atingiu o limite de tokens e pode estar incompleta (ANTHROPIC_MAX_TOKENS).';
        }

        return new RespostaIA($texto, (string) $resposta->json('model', $this->modelo), $avisos);
    }

    private function bloco(AnexoIA $anexo): ?array
    {
        $ext = strtolower(pathinfo($anexo->nome, PATHINFO_EXTENSION));

        if ($anexo->mimeType === 'application/pdf' || $ext === 'pdf') {
            return $this->documento(['type' => 'base64', 'media_type' => 'application/pdf', 'data' => base64_encode($anexo->conteudo)], $anexo->nome);
        }
        if (in_array($anexo->mimeType, self::IMAGENS, true)) {
            return ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $anexo->mimeType, 'data' => base64_encode($anexo->conteudo)]];
        }
        if ($ext === 'docx' && ($texto = $this->textoDocx($anexo->conteudo))) {
            return $this->documento(['type' => 'text', 'media_type' => 'text/plain', 'data' => $texto], $anexo->nome);
        }
        if (in_array($ext, self::TEXTO_EXT, true) || str_starts_with($anexo->mimeType, 'text/')) {
            $texto = mb_check_encoding($anexo->conteudo, 'UTF-8') ? $anexo->conteudo : mb_convert_encoding($anexo->conteudo, 'UTF-8', 'ISO-8859-1');

            return $this->documento(['type' => 'text', 'media_type' => 'text/plain', 'data' => $texto], $anexo->nome);
        }

        return null;
    }

    private function documento(array $source, string $nome): array
    {
        return ['type' => 'document', 'source' => $source, 'title' => $nome];
    }

    private function textoDocx(string $bytes): ?string
    {
        if (! class_exists(\ZipArchive::class)) {
            return null;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($tmp, $bytes);
        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmp) !== true) {
                return null;
            }
            $xml = $zip->getFromName('word/document.xml') ?: '';
            $zip->close();
            $xml = preg_replace(['#</w:p>#', '#<w:tab/>#', '#<w:br/>#'], ["\n", "\t", "\n"], $xml);

            return trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8')) ?: null;
        } finally {
            @unlink($tmp);
        }
    }
}
