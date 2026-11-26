<?php

namespace Src\Application\Modulo\UseCases;

use Src\Domain\Modulo\Entities\Modulo;
use Src\Domain\Modulo\Repositories\ModuloRepositoryInterface;
use Src\Domain\Shared\Exceptions\DomainException;
use Src\Domain\Shared\Exceptions\EntidadeNaoEncontradaException;
use Src\Domain\Shared\Support\Texto;

final class GerenciarModulos
{
    public function __construct(private readonly ModuloRepositoryInterface $modulos)
    {
    }

    public function listar(): array
    {
        $contagens = $this->modulos->contagens();

        return array_map(fn (Modulo $m) => $m->toArray() + [
            'total_regras' => $contagens[$m->id()]['regras'] ?? 0,
            'total_documentos' => $contagens[$m->id()]['documentos'] ?? 0,
        ], $this->modulos->listar());
    }

    public function salvar(?int $id, array $dados): array
    {
        $tag = Texto::tag((string) (($dados['tag'] ?? '') ?: ($dados['nome'] ?? '')));
        $existente = $tag !== '' ? $this->modulos->buscarPorTag($tag) : null;
        if ($existente && $existente->id() !== $id) {
            throw new DomainException("Já existe um módulo com a tag {$tag}.");
        }

        if ($id) {
            $modulo = $this->modulos->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Módulo', $id);
            $modulo->atualizar((string) ($dados['nome'] ?? ''), $dados['tag'] ?? null, $dados['descricao'] ?? null, $dados['cor'] ?? null);
        } else {
            $modulo = Modulo::criar((string) ($dados['nome'] ?? ''), $dados['tag'] ?? null, $dados['descricao'] ?? null, $dados['cor'] ?? null);
        }

        return $this->modulos->salvar($modulo)->toArray();
    }

    public function excluir(int $id): void
    {
        $this->modulos->buscarPorId($id) ?? throw EntidadeNaoEncontradaException::para('Módulo', $id);
        $c = $this->modulos->contagens()[$id] ?? ['regras' => 0, 'documentos' => 0];
        if ($c['regras'] > 0 || $c['documentos'] > 0) {
            throw new DomainException(sprintf(
                'O módulo possui %d regra(s) e %d documento(s). Mova ou exclua antes de remover o módulo.', $c['regras'], $c['documentos'],
            ));
        }
        $this->modulos->excluir($id);
    }
}
