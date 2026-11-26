<?php

namespace Src\Application\RegraNegocio\UseCases;

use Src\Application\CasoTeste\Services\CriadorCasosEmPlano;
use Src\Domain\Cofre\Repositories\DocumentoRepositoryInterface;
use Src\Domain\CasoTeste\Enums\OrigemCaso;
use Src\Domain\Modulo\Repositories\ModuloRepositoryInterface;
use Src\Domain\RegraNegocio\Entities\RegraNegocio;
use Src\Domain\RegraNegocio\Enums\DecisaoValidacao;
use Src\Domain\RegraNegocio\Enums\PapelValidador;
use Src\Domain\RegraNegocio\Enums\StatusRegra;
use Src\Domain\RegraNegocio\Enums\TipoRegra;
use Src\Domain\RegraNegocio\Repositories\RegraNegocioRepositoryInterface;
use Src\Domain\RegraNegocio\Services\ExtratorMarcadores;
use Src\Domain\RegraNegocio\ValueObjects\Marcador;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

/**
 * Casos de uso da validação de regras de negócio (PM/PO).
 */
final class GerenciarRegras
{
    public const ACAO_ENVIAR = 'enviar';
    public const ACAO_RETORNAR = 'retornar';
    public const ACAO_REABRIR = 'reabrir';

    public function __construct(
        private readonly RegraNegocioRepositoryInterface $regras,
        private readonly ModuloRepositoryInterface $modulos,
        private readonly DocumentoRepositoryInterface $documentos,
        private readonly ExtratorMarcadores $extrator,
        private readonly CriadorCasosEmPlano $criadorCasos,
    ) {
    }

    public function listar(?int $moduloId, ?string $status, ?string $tipo, ?string $busca): array
    {
        $modulos = $this->mapaModulos();

        return array_map(function (RegraNegocio $r) use ($modulos) {
            $dados = $r->toArray();
            unset($dados['conteudo']);
            $marcadores = $this->extrator->extrair($r->conteudo());

            return $dados + [
                'modulo' => $modulos[$r->moduloId()] ?? null,
                'contagem_marcadores' => $this->contar($marcadores),
            ];
        }, $this->regras->listar($moduloId, $status, $tipo, $busca));
    }

    public function obter(int $id): array
    {
        $regra = $this->buscar($id);
        $marcadores = $this->extrator->extrair($regra->conteudo());

        return $regra->toArray() + [
            'modulo' => $this->modulos->buscarPorId($regra->moduloId())?->toArray(),
            'marcadores' => array_map(fn (Marcador $m) => $m->toArray(), $marcadores),
            'contagem_marcadores' => $this->contar($marcadores),
            'casos_extraidos' => array_map(fn ($c) => $c->toArray(), $this->extrator->casos($regra->conteudo())),
            'total_documentos' => count($this->documentos->listar(['regra_id' => $id])),
        ];
    }

    /** Pré-visualização dos marcadores enquanto o usuário digita (sem salvar). */
    public function analisar(string $conteudo): array
    {
        $marcadores = $this->extrator->extrair($conteudo);

        return [
            'marcadores' => array_map(fn (Marcador $m) => $m->toArray(), $marcadores),
            'contagem_marcadores' => $this->contar($marcadores),
            'casos_extraidos' => array_map(fn ($c) => $c->toArray(), $this->extrator->casos($conteudo)),
        ];
    }

    public function criar(array $d): array
    {
        $this->garantirModulo((int) ($d['modulo_id'] ?? 0));
        $regra = RegraNegocio::criar(
            (int) $d['modulo_id'],
            (string) ($d['titulo'] ?? ''),
            TipoRegra::tryFrom((string) ($d['tipo'] ?? '')) ?? throw new DomainException('Tipo de regra inválido.'),
            (string) ($d['conteudo'] ?? ''),
            $d['autor'] ?? null,
        );

        return $this->obter($this->regras->salvar($regra)->id());
    }

    public function editar(int $id, array $d): array
    {
        $regra = $this->buscar($id);
        $this->garantirModulo((int) ($d['modulo_id'] ?? 0));
        $regra->editar(
            (int) $d['modulo_id'],
            (string) ($d['titulo'] ?? ''),
            TipoRegra::tryFrom((string) ($d['tipo'] ?? '')) ?? throw new DomainException('Tipo de regra inválido.'),
            (string) ($d['conteudo'] ?? ''),
            $d['autor'] ?? null,
        );
        $this->regras->salvar($regra);

        return $this->obter($id);
    }

    public function alterarFluxo(int $id, string $acao): array
    {
        $regra = $this->buscar($id);
        match ($acao) {
            self::ACAO_ENVIAR => $regra->enviarParaValidacao(),
            self::ACAO_RETORNAR => $regra->retornarParaRascunho(),
            self::ACAO_REABRIR => $regra->reabrir(),
            default => throw new DomainException("Ação inválida: {$acao}."),
        };
        $this->regras->salvar($regra);

        return $this->obter($id);
    }

    public function registrarParecer(int $id, array $d): array
    {
        $regra = $this->buscar($id);
        $regra->registrarParecer(
            PapelValidador::tryFrom((string) ($d['papel'] ?? '')) ?? throw new DomainException('Papel inválido (pm ou po).'),
            DecisaoValidacao::tryFrom((string) ($d['decisao'] ?? '')) ?? throw new DomainException('Decisão inválida.'),
            (string) ($d['responsavel'] ?? ''),
            $d['parecer'] ?? null,
        );
        $this->regras->salvar($regra);

        return $this->obter($id);
    }

    public function versoes(int $id): array
    {
        $this->buscar($id);

        return $this->regras->versoes($id);
    }

    public function excluir(int $id): void
    {
        $regra = $this->buscar($id);
        if ($regra->status() === StatusRegra::APROVADA) {
            throw new DomainException('Regras aprovadas não podem ser excluídas. Reabra a regra primeiro.');
        }
        // os documentos continuam no cofre, no módulo da regra
        $this->regras->excluir($id);
    }

    /**
     * Gera casos de teste a partir dos marcadores "caso" da regra.
     *
     * @param int[]|null $indices  quais casos extraídos levar (null = todos)
     */
    public function gerarPlano(int $id, ?int $planoId, ?array $indices): array
    {
        $regra = $this->buscar($id);
        $casos = $this->extrator->casos($regra->conteudo());

        if ($casos === []) {
            throw new DomainException('A regra não tem trechos marcados como "caso". Use a tag Caso no editor.');
        }
        if ($indices !== null) {
            $casos = array_values(array_intersect_key($casos, array_flip($indices)));
        }

        $modulo = $this->modulos->buscarPorId($regra->moduloId());

        return $this->criadorCasos->executar(
            $casos,
            OrigemCaso::REGRA,
            // referência estável por regra+título: regerar na mesma versão não duplica
            fn ($caso) => mb_substr(sprintf('regra:%d:%s', $regra->id(), mb_strtolower($caso->titulo)), 0, 150),
            $planoId,
            [
                'nome' => "Regra: {$regra->titulo()}",
                'projeto' => $modulo?->nome(),
                'versao' => "regra v{$regra->versao()}",
                'descricao' => "Gerado a partir da regra de negócio #{$regra->id()} ({$regra->status()->rotulo()}).",
            ],
        );
    }

    private function buscar(int $id): RegraNegocio
    {
        return $this->regras->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Regra de negócio', $id);
    }

    private function garantirModulo(int $moduloId): void
    {
        $this->modulos->buscarPorId($moduloId) ?? throw new DomainException('Selecione um módulo válido.');
    }

    private function mapaModulos(): array
    {
        $mapa = [];
        foreach ($this->modulos->listar() as $m) {
            $mapa[$m->id()] = $m->toArray();
        }

        return $mapa;
    }

    /** @param Marcador[] $marcadores */
    private function contar(array $marcadores): array
    {
        $contagem = ['caso' => 0, 'regra' => 0, 'criterio' => 0, 'excecao' => 0, 'duvida' => 0];
        foreach ($marcadores as $m) {
            $contagem[$m->tipo->value]++;
        }

        return $contagem;
    }
}
