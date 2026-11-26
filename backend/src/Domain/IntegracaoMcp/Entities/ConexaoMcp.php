<?php

namespace Src\Domain\IntegracaoMcp\Entities;

use DateTimeImmutable;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Support\Texto;

/**
 * Servidor MCP da empresa (transporte Streamable HTTP) que fornece os casos de teste oficiais.
 */
final class ConexaoMcp
{
    /** @param array<string, string> $cabecalhos  @param array<string, mixed> $argumentosPadrao */
    private function __construct(
        private ?int $id,
        private string $nome,
        private string $url,
        private ?string $token,
        private array $cabecalhos,
        private ?string $ferramentaCasos,
        private array $argumentosPadrao,
        private bool $ativo,
        private ?DateTimeImmutable $ultimoTesteEm = null,
        private ?bool $ultimoTesteOk = null,
        private ?string $ultimoTesteMensagem = null,
    ) {
        $this->validar();
    }

    public static function criar(
        string $nome, string $url, ?string $token = null, array $cabecalhos = [], ?string $ferramentaCasos = null, array $argumentosPadrao = [],
    ): self {
        return new self(null, trim($nome), trim($url), Texto::vazioParaNull($token), $cabecalhos,
            Texto::vazioParaNull($ferramentaCasos), $argumentosPadrao, true);
    }

    public static function restaurar(
        int $id, string $nome, string $url, ?string $token, array $cabecalhos, ?string $ferramentaCasos, array $argumentosPadrao,
        bool $ativo, ?DateTimeImmutable $ultimoTesteEm, ?bool $ultimoTesteOk, ?string $ultimoTesteMensagem,
    ): self {
        return new self($id, $nome, $url, $token, $cabecalhos, $ferramentaCasos, $argumentosPadrao, $ativo,
            $ultimoTesteEm, $ultimoTesteOk, $ultimoTesteMensagem);
    }

    /** $token null = mantém o atual; string vazia = remove. */
    public function atualizar(
        string $nome, string $url, ?string $token, array $cabecalhos, ?string $ferramentaCasos, array $argumentosPadrao, bool $ativo,
    ): void {
        $this->nome = trim($nome);
        $this->url = trim($url);
        if ($token !== null) {
            $this->token = Texto::vazioParaNull($token);
        }
        $this->cabecalhos = $cabecalhos;
        $this->ferramentaCasos = Texto::vazioParaNull($ferramentaCasos);
        $this->argumentosPadrao = $argumentosPadrao;
        $this->ativo = $ativo;
        $this->validar();
    }

    public function registrarTeste(bool $ok, string $mensagem): void
    {
        $this->ultimoTesteEm = new DateTimeImmutable();
        $this->ultimoTesteOk = $ok;
        $this->ultimoTesteMensagem = mb_substr($mensagem, 0, 500);
    }

    public function garantirAtiva(): void
    {
        if (! $this->ativo) {
            throw new DomainException("A conexão MCP \"{$this->nome}\" está desativada.");
        }
    }

    private function validar(): void
    {
        if ($this->nome === '' || mb_strlen($this->nome) > 100) {
            throw new DomainException('O nome da conexão é obrigatório (máx. 100 caracteres).');
        }
        if (! filter_var($this->url, FILTER_VALIDATE_URL) || ! preg_match('#^https?://#i', $this->url)) {
            throw new DomainException('Informe a URL HTTP(S) do servidor MCP (ex.: https://mcp.empresa.com/mcp).');
        }
        foreach ($this->cabecalhos as $nome => $valor) {
            if (! is_string($nome) || ! preg_match('/^[A-Za-z0-9-]+$/', $nome) || ! is_scalar($valor)) {
                throw new DomainException('Cabeçalhos devem ser pares "Nome-Do-Header": "valor".');
            }
        }
    }

    public function definirId(int $id): void { $this->id ??= $id; }
    public function id(): ?int { return $this->id; }
    public function nome(): string { return $this->nome; }
    public function url(): string { return $this->url; }
    public function token(): ?string { return $this->token; }
    public function cabecalhos(): array { return $this->cabecalhos; }
    public function ferramentaCasos(): ?string { return $this->ferramentaCasos; }
    public function argumentosPadrao(): array { return $this->argumentosPadrao; }
    public function ativo(): bool { return $this->ativo; }
    public function ultimoTesteEm(): ?DateTimeImmutable { return $this->ultimoTesteEm; }
    public function ultimoTesteOk(): ?bool { return $this->ultimoTesteOk; }
    public function ultimoTesteMensagem(): ?string { return $this->ultimoTesteMensagem; }

    /** Nunca expõe o token. */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'url' => $this->url,
            'possui_token' => $this->token !== null,
            'cabecalhos' => array_map(fn () => '••••', $this->cabecalhos),
            'ferramenta_casos' => $this->ferramentaCasos,
            'argumentos_padrao' => (object) $this->argumentosPadrao,
            'ativo' => $this->ativo,
            'ultimo_teste_em' => $this->ultimoTesteEm?->format(DATE_ATOM),
            'ultimo_teste_ok' => $this->ultimoTesteOk,
            'ultimo_teste_mensagem' => $this->ultimoTesteMensagem,
        ];
    }
}
