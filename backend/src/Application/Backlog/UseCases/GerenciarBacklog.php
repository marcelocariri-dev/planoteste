<?php

namespace Src\Application\Backlog\UseCases;

use Src\Application\Backlog\Ports\AnexoIA;
use Src\Application\Backlog\Ports\GeradorBacklogInterface;
use Src\Application\CasoTeste\Services\CriadorCasosEmPlano;
use Src\Application\Shared\ArmazenamentoArquivosInterface;
use Src\Domain\Backlog\Entities\Backlog;
use Src\Domain\Backlog\Entities\PadraoBacklog;
use Src\Domain\Backlog\Repositories\BacklogRepositoryInterface;
use Src\Domain\Backlog\Repositories\PadraoBacklogRepositoryInterface;
use Src\Domain\Backlog\Services\InterpretadorRespostaBacklog;
use Src\Domain\Backlog\Services\MontadorPromptBacklog;
use Src\Domain\Backlog\ValueObjects\Epico;
use Src\Domain\Backlog\ValueObjects\Historia;
use Src\Domain\CasoTeste\Enums\OrigemCaso;
use Src\Domain\CasoTeste\Enums\Prioridade;
use Src\Domain\Cofre\Repositories\DocumentoRepositoryInterface;
use Src\Domain\Modulo\Repositories\ModuloRepositoryInterface;
use Src\Domain\RegraNegocio\Repositories\RegraNegocioRepositoryInterface;
use Src\Domain\RegraNegocio\ValueObjects\CasoExtraido;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

/**
 * Gerador de product backlog a partir de orientações, regras de negócio e documentos do cofre.
 */
final class GerenciarBacklog
{
    public function __construct(
        private readonly BacklogRepositoryInterface $backlogs,
        private readonly PadraoBacklogRepositoryInterface $padroes,
        private readonly ModuloRepositoryInterface $modulos,
        private readonly RegraNegocioRepositoryInterface $regras,
        private readonly DocumentoRepositoryInterface $documentos,
        private readonly ArmazenamentoArquivosInterface $armazenamento,
        private readonly GeradorBacklogInterface $gerador,
        private readonly MontadorPromptBacklog $montador,
        private readonly InterpretadorRespostaBacklog $interpretador,
        private readonly CriadorCasosEmPlano $criadorCasos,
    ) {
    }

    public function configuracao(): array
    {
        return ['ia_disponivel' => $this->gerador->disponivel(), 'variaveis' => PadraoBacklog::VARIAVEIS];
    }

    // ---------------- padrões

    public function listarPadroes(): array
    {
        return array_map(fn (PadraoBacklog $p) => $p->toArray(), $this->padroes->listar());
    }

    public function salvarPadrao(?int $id, array $d): array
    {
        if ($id) {
            $padrao = $this->padroes->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Padrão de backlog', $id);
            $padrao->atualizar((string) ($d['nome'] ?? ''), (string) ($d['instrucoes'] ?? ''), $d['descricao'] ?? null);
        } else {
            $padrao = PadraoBacklog::criar((string) ($d['nome'] ?? ''), (string) ($d['instrucoes'] ?? ''), $d['descricao'] ?? null);
        }

        return $this->padroes->salvar($padrao)->toArray();
    }

    public function excluirPadrao(int $id): void
    {
        $this->padroes->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Padrão de backlog', $id);
        if (count($this->padroes->listar()) <= 1) {
            throw new DomainException('Mantenha pelo menos um padrão cadastrado.');
        }
        $this->padroes->excluir($id);
    }

    // ---------------- backlogs

    public function listar(?int $moduloId): array
    {
        return array_map(fn (Backlog $b) => $b->toArray(false), $this->backlogs->listar($moduloId));
    }

    public function obter(int $id): array
    {
        return $this->buscar($id)->toArray();
    }

    public function gerar(array $d): array
    {
        $padrao = $this->padroes->buscarPorId((int) ($d['padrao_id'] ?? 0)) ?? throw new DomainException('Selecione um padrão de backlog.');
        $moduloId = ! empty($d['modulo_id']) ? (int) $d['modulo_id'] : null;
        $modulo = $moduloId ? ($this->modulos->buscarPorId($moduloId) ?? throw new DomainException('Módulo inválido.')) : null;

        $regras = [];
        $regraIds = array_values(array_unique(array_map('intval', (array) ($d['regra_ids'] ?? []))));
        foreach ($regraIds as $regraId) {
            $regra = $this->regras->buscarPorId($regraId) ?? throw EntidadeNaoEncontradaException::para('Regra de negócio', $regraId);
            $regras[] = [
                'titulo' => $regra->titulo(),
                'modulo' => $this->modulos->buscarPorId($regra->moduloId())?->nome(),
                'versao' => $regra->versao(),
                'status' => $regra->status()->rotulo(),
                'conteudo' => $regra->conteudo(),
            ];
        }

        $documentoIds = array_values(array_unique(array_map('intval', (array) ($d['documento_ids'] ?? []))));
        $documentos = $this->documentos->buscarVarios($documentoIds);
        if (count($documentos) !== count($documentoIds)) {
            throw new DomainException('Um ou mais documentos selecionados não existem mais no cofre.');
        }

        $prompt = $this->montador->montar(
            $padrao,
            (string) ($d['orientacoes'] ?? ''),
            $modulo?->nome(),
            $regras,
            array_map(fn ($doc) => "{$doc->titulo()} ({$doc->arquivo()->nomeOriginal})", $documentos),
        );

        $backlog = Backlog::solicitar(
            (string) ($d['titulo'] ?? ''), $moduloId, $padrao->id(), (string) ($d['orientacoes'] ?? ''),
            $documentoIds, $regraIds, $prompt,
        );
        $backlog = $this->backlogs->salvar($backlog);

        if (! $this->gerador->disponivel()) {
            return $backlog->toArray(); // status aguardando_ia: o usuário copia o prompt e cola a resposta
        }

        try {
            $anexos = array_map(fn ($doc) => new AnexoIA(
                $doc->arquivo()->nomeOriginal,
                $doc->arquivo()->mimeType,
                $this->armazenamento->conteudo($doc->arquivo()->caminho),
            ), $documentos);

            $resposta = $this->gerador->gerar($prompt, $anexos);
            $this->aplicarResposta($backlog, $resposta->texto, $resposta->modelo);
        } catch (DomainException $e) {
            $backlog->registrarFalha($e->getMessage(), $resposta->texto ?? null);
        } catch (\Throwable $e) {
            $backlog->registrarFalha('Falha ao chamar a IA: ' . $e->getMessage());
        }

        return $this->backlogs->salvar($backlog)->toArray();
    }

    /** Resposta colada manualmente (gerada no Claude com o prompt copiado). */
    public function importarResposta(int $id, string $texto): array
    {
        $backlog = $this->buscar($id);
        $this->aplicarResposta($backlog, $texto, 'manual');

        return $this->backlogs->salvar($backlog)->toArray();
    }

    public function excluir(int $id): void
    {
        $this->buscar($id);
        $this->backlogs->excluir($id);
    }

    /**
     * Cada história vira um caso de teste; os critérios de aceite viram passos/resultado esperado.
     *
     * @param string[]|null $codigos  histórias escolhidas (null = todas)
     */
    public function gerarPlano(int $id, ?int $planoId, ?array $codigos): array
    {
        $backlog = $this->buscar($id);
        $candidatos = [];

        foreach ($backlog->epicos() as $epico) {
            foreach ($epico->historias as $h) {
                if ($codigos === null || in_array($h->codigo, $codigos, true)) {
                    $candidatos[] = $this->historiaParaCaso($epico, $h);
                }
            }
        }

        $modulo = $backlog->moduloId() ? $this->modulos->buscarPorId($backlog->moduloId()) : null;

        return $this->criadorCasos->executar(
            $candidatos,
            OrigemCaso::BACKLOG,
            fn (CasoExtraido $c) => "backlog:{$backlog->id()}:{$c->idExterno}",
            $planoId,
            ['nome' => "Backlog: {$backlog->titulo()}", 'projeto' => $modulo?->nome(), 'descricao' => "Gerado do product backlog #{$backlog->id()}."],
        );
    }

    private function aplicarResposta(Backlog $backlog, string $texto, string $modelo): void
    {
        $backlog->registrarResultado($this->interpretador->interpretar($texto), $texto, $modelo);
    }

    private function historiaParaCaso(Epico $epico, Historia $h): CasoExtraido
    {
        $dado = $quando = $entao = [];
        foreach ($h->criteriosAceite as $criterio) {
            // separa "Dado ... Quando ... Então ..." de um critério em uma linha
            $partes = preg_split('/\s+(?=(?:quando|ent[ãa]o|e)\s)/iu', $criterio);
            foreach ($partes as $p) {
                match (true) {
                    (bool) preg_match('/^dado\b/iu', $p) => $dado[] = $p,
                    (bool) preg_match('/^quando\b/iu', $p) => $quando[] = $p,
                    (bool) preg_match('/^ent[ãa]o\b/iu', $p) => $entao[] = $p,
                    default => $quando[] = $p,
                };
            }
        }

        $prioridade = match (mb_strtolower($h->prioridade)) {
            'must' => Prioridade::ALTA,
            'could', "won't", 'wont' => Prioridade::BAIXA,
            default => Prioridade::MEDIA,
        };

        $numerar = fn (array $l) => implode("\n", array_map(fn ($x, $i) => ($i + 1) . '. ' . $x, $l, array_keys($l)));

        return new CasoExtraido(
            titulo: mb_substr("{$h->codigo} - {$h->titulo}", 0, 200),
            passos: $quando ? $numerar($quando) : "Como {$h->comoUm}, {$h->quero}.",
            resultadoEsperado: $entao ? implode("\n", $entao) : ($h->para ? "Benefício atendido: {$h->para}" : 'Critérios de aceite atendidos.'),
            prioridade: $prioridade,
            preCondicoes: trim("Épico {$epico->codigo}: {$epico->titulo}" . ($dado ? "\n" . implode("\n", $dado) : '')),
            idExterno: $h->codigo,
        );
    }

    private function buscar(int $id): Backlog
    {
        return $this->backlogs->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Backlog', $id);
    }
}
