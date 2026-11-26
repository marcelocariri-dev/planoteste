<?php

namespace Src\Application\Cofre\UseCases;

use Src\Application\Shared\ArmazenamentoArquivosInterface;
use Src\Application\Shared\ArquivoRecebido;
use Src\Application\Shared\TransactionManagerInterface;
use Src\Domain\Cofre\Entities\Categoria;
use Src\Domain\Cofre\Entities\Documento;
use Src\Domain\Cofre\Repositories\CategoriaRepositoryInterface;
use Src\Domain\Cofre\Repositories\DocumentoRepositoryInterface;
use Src\Domain\Cofre\ValueObjects\Arquivo;
use Src\Domain\Modulo\Repositories\ModuloRepositoryInterface;
use Src\Domain\RegraNegocio\Repositories\RegraNegocioRepositoryInterface;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;

/**
 * Cofre de documentos e normas técnicas.
 */
final class GerenciarCofre
{
    public function __construct(
        private readonly DocumentoRepositoryInterface $documentos,
        private readonly CategoriaRepositoryInterface $categorias,
        private readonly ModuloRepositoryInterface $modulos,
        private readonly RegraNegocioRepositoryInterface $regras,
        private readonly ArmazenamentoArquivosInterface $armazenamento,
        private readonly TransactionManagerInterface $transacao,
    ) {
    }

    // ---------------- categorias

    public function listarCategorias(): array
    {
        return array_map(fn (Categoria $c) => $c->toArray() + ['total_documentos' => $this->categorias->contarDocumentos($c->id())],
            $this->categorias->listar());
    }

    public function salvarCategoria(?int $id, string $nome, ?string $descricao): array
    {
        foreach ($this->categorias->listar() as $c) {
            if (mb_strtolower($c->nome()) === mb_strtolower(trim($nome)) && $c->id() !== $id) {
                throw new DomainException('Já existe uma categoria com esse nome.');
            }
        }
        $categoria = $id
            ? Categoria::restaurar(($this->categorias->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Categoria', $id))->id(), trim($nome), $descricao)
            : Categoria::criar($nome, $descricao);

        return $this->categorias->salvar($categoria)->toArray();
    }

    public function excluirCategoria(int $id): void
    {
        $this->categorias->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Categoria', $id);
        if ($this->categorias->contarDocumentos($id) > 0) {
            throw new DomainException('A categoria possui documentos. Mova-os para outra categoria antes de excluir.');
        }
        $this->categorias->excluir($id);
    }

    // ---------------- documentos

    public function listar(array $filtros): array
    {
        return array_map(fn (Documento $d) => $d->toArray(), $this->documentos->listar($filtros));
    }

    /** Upload avulso (regraId null) ou anexo de uma regra de negócio. */
    public function enviar(ArquivoRecebido $recebido, array $d): array
    {
        $categoriaId = (int) ($d['categoria_id'] ?? 0);
        $this->categorias->buscarPorId($categoriaId) ?? throw new DomainException('Selecione uma categoria válida.');

        $regraId = isset($d['regra_id']) ? (int) $d['regra_id'] : null;
        $moduloId = isset($d['modulo_id']) && $d['modulo_id'] !== '' ? (int) $d['modulo_id'] : null;

        if ($regraId) { // anexo de regra vai sempre para o módulo da regra
            $regra = $this->regras->buscarPorId($regraId) ?? throw EntidadeNaoEncontradaException::para('Regra de negócio', $regraId);
            $moduloId = $regra->moduloId();
        }

        $modulo = $moduloId ? ($this->modulos->buscarPorId($moduloId) ?? throw new DomainException('Módulo inválido.')) : null;

        // valida tamanho etc. antes de gravar o arquivo
        new Arquivo('pendente', $recebido->nomeOriginal, $recebido->mimeType, $recebido->tamanho);

        $caminho = $this->armazenamento->guardar($recebido, $modulo?->tag() ?? 'GERAL');

        try {
            $documento = Documento::enviar(
                titulo: (string) ($d['titulo'] ?? ''),
                categoriaId: $categoriaId,
                arquivo: new Arquivo($caminho, $recebido->nomeOriginal, $recebido->mimeType, $recebido->tamanho),
                moduloId: $moduloId,
                regraId: $regraId,
                descricao: $d['descricao'] ?? null,
                tags: $this->tags($d['tags'] ?? []),
                enviadoPor: $d['enviado_por'] ?? null,
            );

            return $this->documentos->salvar($documento)->toArray();
        } catch (\Throwable $e) {
            $this->armazenamento->remover($caminho); // não deixa arquivo órfão
            throw $e;
        }
    }

    public function atualizar(int $id, array $d): array
    {
        $documento = $this->buscar($id);
        $categoriaId = (int) ($d['categoria_id'] ?? $documento->categoriaId());
        $this->categorias->buscarPorId($categoriaId) ?? throw new DomainException('Selecione uma categoria válida.');
        $moduloId = array_key_exists('modulo_id', $d) ? ($d['modulo_id'] ? (int) $d['modulo_id'] : null) : $documento->moduloId();
        if ($moduloId) {
            $this->modulos->buscarPorId($moduloId) ?? throw new DomainException('Módulo inválido.');
        }

        $documento->atualizar((string) ($d['titulo'] ?? $documento->titulo()), $d['descricao'] ?? $documento->descricao(),
            $categoriaId, $moduloId, $this->tags($d['tags'] ?? $documento->tags()));

        return $this->documentos->salvar($documento)->toArray();
    }

    public function excluir(int $id): void
    {
        $documento = $this->buscar($id);
        $this->transacao->executar(fn () => $this->documentos->excluir($id));
        $this->armazenamento->remover($documento->arquivo()->caminho);
    }

    /** @return array{caminho: string, nome: string, mime: string} */
    public function arquivoParaDownload(int $id): array
    {
        $documento = $this->buscar($id);
        $arquivo = $documento->arquivo();
        if (! $this->armazenamento->existe($arquivo->caminho)) {
            throw new DomainException('O arquivo físico não foi encontrado no cofre.');
        }

        return [
            'caminho' => $this->armazenamento->caminhoAbsoluto($arquivo->caminho),
            'nome' => $arquivo->nomeOriginal,
            'mime' => $arquivo->mimeType,
        ];
    }

    private function buscar(int $id): Documento
    {
        return $this->documentos->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Documento', $id);
    }

    private function tags(mixed $tags): array
    {
        return is_string($tags) ? preg_split('/[,;]/', $tags) : (array) $tags;
    }
}
