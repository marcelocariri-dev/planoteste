<?php

namespace Src\Domain\RegraNegocio\Entities;

use DateTimeImmutable;
use Src\Domain\RegraNegocio\Enums\DecisaoValidacao;
use Src\Domain\RegraNegocio\Enums\PapelValidador;
use Src\Domain\RegraNegocio\Enums\StatusRegra;
use Src\Domain\RegraNegocio\Enums\TipoRegra;
use Src\Domain\RegraNegocio\ValueObjects\Validacao;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Support\Texto;

/**
 * Documento de regra de negócio de uma melhoria ou de um novo módulo, validado por PM e PO.
 *
 * Fluxo: rascunho -> em_validacao -> aprovada | reprovada
 *        reprovada --(edição)--> rascunho ; aprovada --(reabrir)--> rascunho (nova versão)
 */
final class RegraNegocio
{
    /** @var array<string, Validacao> */
    private array $validacoes;

    private bool $conteudoAlterado = false;

    private function __construct(
        private ?int $id,
        private int $moduloId,
        private string $titulo,
        private TipoRegra $tipo,
        private string $conteudo,
        private StatusRegra $status,
        private int $versao,
        private ?string $autor,
        array $validacoes = [],
        private ?DateTimeImmutable $atualizadoEm = null,
    ) {
        $this->validacoes = [
            PapelValidador::PM->value => $validacoes[PapelValidador::PM->value] ?? Validacao::pendente(PapelValidador::PM),
            PapelValidador::PO->value => $validacoes[PapelValidador::PO->value] ?? Validacao::pendente(PapelValidador::PO),
        ];
        $this->validar();
    }

    public static function criar(int $moduloId, string $titulo, TipoRegra $tipo, string $conteudo = '', ?string $autor = null): self
    {
        $regra = new self(null, $moduloId, trim($titulo), $tipo, $conteudo, StatusRegra::RASCUNHO, 1, Texto::vazioParaNull($autor));
        $regra->conteudoAlterado = true;

        return $regra;
    }

    /** @param array<string, Validacao> $validacoes */
    public static function restaurar(
        int $id, int $moduloId, string $titulo, TipoRegra $tipo, string $conteudo, StatusRegra $status,
        int $versao, ?string $autor, array $validacoes, ?DateTimeImmutable $atualizadoEm,
    ): self {
        return new self($id, $moduloId, $titulo, $tipo, $conteudo, $status, $versao, $autor, $validacoes, $atualizadoEm);
    }

    public function editar(int $moduloId, string $titulo, TipoRegra $tipo, string $conteudo, ?string $autor): void
    {
        if (! $this->status->permiteEdicao()) {
            throw new DomainException(match ($this->status) {
                StatusRegra::EM_VALIDACAO => 'A regra está em validação. Retorne para rascunho antes de editar.',
                default => 'A regra está aprovada. Reabra a regra para criar uma nova versão.',
            });
        }

        $mudouTexto = trim($titulo) !== $this->titulo || $conteudo !== $this->conteudo;

        $this->moduloId = $moduloId;
        $this->titulo = trim($titulo);
        $this->tipo = $tipo;
        $this->conteudo = $conteudo;
        $this->autor = Texto::vazioParaNull($autor) ?? $this->autor;
        $this->validar();

        if ($this->status === StatusRegra::REPROVADA) {
            $this->status = StatusRegra::RASCUNHO;
            $this->zerarValidacoes();
        }

        if ($mudouTexto) {
            $this->versao++;
            $this->conteudoAlterado = true;
        }
    }

    public function enviarParaValidacao(): void
    {
        if ($this->status !== StatusRegra::RASCUNHO) {
            throw new DomainException('Somente regras em rascunho podem ser enviadas para validação.');
        }
        if (trim($this->conteudo) === '') {
            throw new DomainException('Escreva a regra de negócio antes de enviar para validação.');
        }

        $this->zerarValidacoes();
        $this->status = StatusRegra::EM_VALIDACAO;
    }

    public function registrarParecer(PapelValidador $papel, DecisaoValidacao $decisao, string $responsavel, ?string $parecer): void
    {
        if ($this->status !== StatusRegra::EM_VALIDACAO) {
            throw new DomainException('A regra precisa estar em validação para receber parecer.');
        }
        if ($decisao === DecisaoValidacao::PENDENTE) {
            throw new DomainException('Informe se aprova ou reprova.');
        }
        if (trim($responsavel) === '') {
            throw new DomainException(sprintf('Informe o nome do %s responsável.', $papel->rotulo()));
        }
        $parecer = Texto::vazioParaNull($parecer);
        if ($decisao === DecisaoValidacao::REPROVADO && $parecer === null) {
            throw new DomainException('Descreva o motivo da reprovação no parecer.');
        }

        $this->validacoes[$papel->value] = new Validacao($papel, $decisao, trim($responsavel), $parecer, new DateTimeImmutable());

        $decisoes = array_map(fn (Validacao $v) => $v->decisao, $this->validacoes);

        if (in_array(DecisaoValidacao::REPROVADO, $decisoes, true)) {
            $this->status = StatusRegra::REPROVADA;
        } elseif (! in_array(DecisaoValidacao::PENDENTE, $decisoes, true)) {
            $this->status = StatusRegra::APROVADA;
        }
    }

    /** Em validação -> rascunho (ex.: autor quer ajustar antes dos pareceres). */
    public function retornarParaRascunho(): void
    {
        if (! in_array($this->status, [StatusRegra::EM_VALIDACAO, StatusRegra::REPROVADA], true)) {
            throw new DomainException('Somente regras em validação ou reprovadas voltam para rascunho.');
        }
        $this->status = StatusRegra::RASCUNHO;
        $this->zerarValidacoes();
    }

    /** Aprovada -> rascunho para uma nova versão. */
    public function reabrir(): void
    {
        if ($this->status !== StatusRegra::APROVADA) {
            throw new DomainException('Somente regras aprovadas podem ser reabertas.');
        }
        $this->status = StatusRegra::RASCUNHO;
        $this->zerarValidacoes();
    }

    private function zerarValidacoes(): void
    {
        foreach (PapelValidador::cases() as $papel) {
            $this->validacoes[$papel->value] = Validacao::pendente($papel);
        }
    }

    private function validar(): void
    {
        if ($this->titulo === '' || mb_strlen($this->titulo) > 200) {
            throw new DomainException('O título da regra é obrigatório (máx. 200 caracteres).');
        }
    }

    public function definirId(int $id): void
    {
        $this->id ??= $id;
    }

    /** Indica se a última operação gerou uma nova versão do texto (para gravar histórico). */
    public function conteudoFoiAlterado(): bool { return $this->conteudoAlterado; }

    public function id(): ?int { return $this->id; }
    public function moduloId(): int { return $this->moduloId; }
    public function titulo(): string { return $this->titulo; }
    public function tipo(): TipoRegra { return $this->tipo; }
    public function conteudo(): string { return $this->conteudo; }
    public function status(): StatusRegra { return $this->status; }
    public function versao(): int { return $this->versao; }
    public function autor(): ?string { return $this->autor; }
    public function validacao(PapelValidador $papel): Validacao { return $this->validacoes[$papel->value]; }
    /** @return array<string, Validacao> */
    public function validacoes(): array { return $this->validacoes; }
    public function atualizadoEm(): ?DateTimeImmutable { return $this->atualizadoEm; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'modulo_id' => $this->moduloId,
            'titulo' => $this->titulo,
            'tipo' => $this->tipo->value,
            'tipo_rotulo' => $this->tipo->rotulo(),
            'conteudo' => $this->conteudo,
            'status' => $this->status->value,
            'status_rotulo' => $this->status->rotulo(),
            'versao' => $this->versao,
            'autor' => $this->autor,
            'validacoes' => array_values(array_map(fn (Validacao $v) => $v->toArray(), $this->validacoes)),
            'atualizado_em' => $this->atualizadoEm?->format(DATE_ATOM),
        ];
    }
}
