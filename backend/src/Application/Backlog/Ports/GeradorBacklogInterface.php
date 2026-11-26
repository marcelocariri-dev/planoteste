<?php

namespace Src\Application\Backlog\Ports;

/**
 * Porta para o modelo de IA que gera o backlog. Implementação atual: API do Claude (Anthropic).
 */
interface GeradorBacklogInterface
{
    /** false quando não há chave de API configurada: o sistema entrega o prompt para uso manual. */
    public function disponivel(): bool;

    /** @param AnexoIA[] $anexos */
    public function gerar(string $prompt, array $anexos): RespostaIA;
}
