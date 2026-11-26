<?php

namespace Src\Domain\Cofre\Entities;

use DateTimeImmutable;
use Src\Domain\Cofre\Enums\OrigemDocumento;
use Src\Domain\Cofre\ValueObjects\Arquivo;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Support\Texto;

/**
 * Documento ou norma técnica guardado no cofre, departamentalizado pela tag do módulo.
 */
final class Documento
{
    /** @param string[] $tags */
    private function __construct(
        private ?int $id,
        private string $titulo,
        private ?string $descricao,
        private int $categoriaId,
        private ?int $moduloId,
        private ?int $regraId,
        private OrigemDocumento $origem,
        private Arquivo $arquivo,
        private array $tags,
        private ?string $enviadoPor,
        private ?DateTimeImmutable $criadoEm = null,
    ) {
        $this->validar();
    }

    /** @param string[] $tags */
    public static function enviar(
        string $titulo, int $categoriaId, Arquivo $arquivo, ?int $moduloId = null, ?int $regraId = null,
        ?string $descricao = null, array $tags = [], ?string $enviadoPor = null,
    ): self {
        if ($regraId !== null && $moduloId === null) {
            throw new DomainException('Documento de uma regra precisa estar no módulo da regra.');
        }

        return new self(
            null,
            trim($titulo) !== '' ? trim($titulo) : pathinfo($arquivo->nomeOriginal, PATHINFO_FILENAME),
            Texto::vazioParaNull($descricao),
            $categoriaId,
            $moduloId,
            $regraId,
            $regraId ? OrigemDocumento::VALIDACAO : OrigemDocumento::AVULSO,
            $arquivo,
            self::normalizarTags($tags),
            Texto::vazioParaNull($enviadoPor),
        );
    }

    public static function restaurar(
        int $id, string $titulo, ?string $descricao, int $categoriaId, ?int $moduloId, ?int $regraId,
        OrigemDocumento $origem, Arquivo $arquivo, array $tags, ?string $enviadoPor, ?DateTimeImmutable $criadoEm,
    ): self {
        return new self($id, $titulo, $descricao, $categoriaId, $moduloId, $regraId, $origem, $arquivo, $tags, $enviadoPor, $criadoEm);
    }

    /** @param string[] $tags */
    public function atualizar(string $titulo, ?string $descricao, int $categoriaId, ?int $moduloId, array $tags): void
    {
        if ($this->regraId !== null && $moduloId !== $this->moduloId) {
            throw new DomainException('Documento anexado a uma regra fica no módulo da regra.');
        }
        $this->titulo = trim($titulo);
        $this->descricao = Texto::vazioParaNull($descricao);
        $this->categoriaId = $categoriaId;
        $this->moduloId = $moduloId;
        $this->tags = self::normalizarTags($tags);
        $this->validar();
    }

    private static function normalizarTags(array $tags): array
    {
        $tags = array_filter(array_map(fn ($t) => Texto::tag((string) $t), $tags));

        return array_values(array_unique($tags));
    }

    private function validar(): void
    {
        if ($this->titulo === '' || mb_strlen($this->titulo) > 200) {
            throw new DomainException('O título do documento é obrigatório (máx. 200 caracteres).');
        }
    }

    public function definirId(int $id): void { $this->id ??= $id; }
    public function id(): ?int { return $this->id; }
    public function titulo(): string { return $this->titulo; }
    public function descricao(): ?string { return $this->descricao; }
    public function categoriaId(): int { return $this->categoriaId; }
    public function moduloId(): ?int { return $this->moduloId; }
    public function regraId(): ?int { return $this->regraId; }
    public function origem(): OrigemDocumento { return $this->origem; }
    public function arquivo(): Arquivo { return $this->arquivo; }
    /** @return string[] */
    public function tags(): array { return $this->tags; }
    public function enviadoPor(): ?string { return $this->enviadoPor; }
    public function criadoEm(): ?DateTimeImmutable { return $this->criadoEm; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'categoria_id' => $this->categoriaId,
            'modulo_id' => $this->moduloId,
            'regra_id' => $this->regraId,
            'origem' => $this->origem->value,
            'origem_rotulo' => $this->origem->rotulo(),
            'arquivo' => $this->arquivo->toArray(),
            'tags' => $this->tags,
            'enviado_por' => $this->enviadoPor,
            'criado_em' => $this->criadoEm?->format(DATE_ATOM),
        ];
    }
}
