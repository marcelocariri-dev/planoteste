<?php

namespace Src\Domain\RegraNegocio\ValueObjects;

use Src\Domain\CasoTeste\Enums\Prioridade;

/** Caso de teste candidato, extraído de um marcador "caso" ou vindo de fonte externa (MCP). */
final class CasoExtraido
{
    public function __construct(
        public readonly string $titulo,
        public readonly string $passos,
        public readonly string $resultadoEsperado,
        public readonly Prioridade $prioridade = Prioridade::MEDIA,
        public readonly ?string $preCondicoes = null,
        public readonly ?string $idExterno = null,
    ) {
    }

    public static function deArray(array $d): self
    {
        return new self(
            titulo: (string) ($d['titulo'] ?? ''),
            passos: (string) ($d['passos'] ?? ''),
            resultadoEsperado: (string) ($d['resultado_esperado'] ?? ''),
            prioridade: Prioridade::tryFrom((string) ($d['prioridade'] ?? '')) ?? Prioridade::MEDIA,
            preCondicoes: $d['pre_condicoes'] ?? null,
            idExterno: isset($d['id_externo']) ? (string) $d['id_externo'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'titulo' => $this->titulo,
            'pre_condicoes' => $this->preCondicoes,
            'passos' => $this->passos,
            'resultado_esperado' => $this->resultadoEsperado,
            'prioridade' => $this->prioridade->value,
            'id_externo' => $this->idExterno,
        ];
    }
}
