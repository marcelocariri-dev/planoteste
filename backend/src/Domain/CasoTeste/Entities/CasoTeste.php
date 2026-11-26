<?php

namespace Src\Domain\CasoTeste\Entities;

use Src\Domain\CasoTeste\Enums\OrigemCaso;
use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\Execucao\Enums\ResultadoExecucao;
use Src\Domain\Shared\Exceptions\DomainException;

/**
 * Caso de teste pertencente a um plano.
 */
final class CasoTeste
{
    private ?ResultadoExecucao $resultadoAtual = null;

    private function __construct(
        private ?int $id,
        private int $planoId,
        private string $titulo,
        private ?string $preCondicoes,
        private string $passos,
        private string $resultadoEsperado,
        private Prioridade $prioridade,
        private int $ordem,
        private OrigemCaso $origem = OrigemCaso::MANUAL,
        private ?string $referencia = null,
    ) {
        $this->validar();
    }

    public static function criar(
        int $planoId,
        string $titulo,
        string $passos,
        string $resultadoEsperado,
        Prioridade $prioridade = Prioridade::MEDIA,
        ?string $preCondicoes = null,
        int $ordem = 0,
        OrigemCaso $origem = OrigemCaso::MANUAL,
        ?string $referencia = null,
    ): self {
        return new self(null, $planoId, trim($titulo), $preCondicoes, trim($passos), trim($resultadoEsperado), $prioridade, $ordem,
            $origem, $referencia !== null ? mb_substr(trim($referencia), 0, 150) : null);
    }

    public static function restaurar(
        int $id,
        int $planoId,
        string $titulo,
        ?string $preCondicoes,
        string $passos,
        string $resultadoEsperado,
        Prioridade $prioridade,
        int $ordem,
        OrigemCaso $origem = OrigemCaso::MANUAL,
        ?string $referencia = null,
    ): self {
        return new self($id, $planoId, $titulo, $preCondicoes, $passos, $resultadoEsperado, $prioridade, $ordem, $origem, $referencia);
    }

    public function atualizar(
        string $titulo,
        string $passos,
        string $resultadoEsperado,
        Prioridade $prioridade,
        ?string $preCondicoes,
        int $ordem,
    ): void {
        $this->titulo = trim($titulo);
        $this->passos = trim($passos);
        $this->resultadoEsperado = trim($resultadoEsperado);
        $this->prioridade = $prioridade;
        $this->preCondicoes = $preCondicoes;
        $this->ordem = $ordem;
        $this->validar();
    }

    private function validar(): void
    {
        if ($this->titulo === '') {
            throw new DomainException('O título do caso de teste é obrigatório.');
        }
        if (mb_strlen($this->titulo) > 200) {
            throw new DomainException('O título do caso de teste deve ter no máximo 200 caracteres.');
        }
        if ($this->passos === '') {
            throw new DomainException('Informe os passos do caso de teste.');
        }
        if ($this->resultadoEsperado === '') {
            throw new DomainException('Informe o resultado esperado do caso de teste.');
        }
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw new DomainException('O caso de teste já possui identificador.');
        }
        $this->id = $id;
    }

    /** Preenchido na leitura com a última execução registrada (null = não executado). */
    public function definirResultadoAtual(?ResultadoExecucao $resultado): void
    {
        $this->resultadoAtual = $resultado;
    }

    public function id(): ?int { return $this->id; }
    public function planoId(): int { return $this->planoId; }
    public function titulo(): string { return $this->titulo; }
    public function preCondicoes(): ?string { return $this->preCondicoes; }
    public function passos(): string { return $this->passos; }
    public function resultadoEsperado(): string { return $this->resultadoEsperado; }
    public function prioridade(): Prioridade { return $this->prioridade; }
    public function ordem(): int { return $this->ordem; }
    public function resultadoAtual(): ?ResultadoExecucao { return $this->resultadoAtual; }
    public function origem(): OrigemCaso { return $this->origem; }
    /** Identificador na origem: id do caso no sistema oficial (MCP), "regra:3:v2", "backlog:5:US-01"... */
    public function referencia(): ?string { return $this->referencia; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'plano_id' => $this->planoId,
            'titulo' => $this->titulo,
            'pre_condicoes' => $this->preCondicoes,
            'passos' => $this->passos,
            'resultado_esperado' => $this->resultadoEsperado,
            'prioridade' => $this->prioridade->value,
            'prioridade_rotulo' => $this->prioridade->rotulo(),
            'ordem' => $this->ordem,
            'resultado_atual' => $this->resultadoAtual?->value ?? 'nao_executado',
            'origem' => $this->origem->value,
            'origem_rotulo' => $this->origem->rotulo(),
            'referencia' => $this->referencia,
        ];
    }
}
