<?php

namespace Src\Domain\IntegracaoMcp\Repositories;

use Src\Domain\IntegracaoMcp\Entities\ConexaoMcp;

interface ConexaoMcpRepositoryInterface
{
    public function buscarPorId(int $id): ?ConexaoMcp;

    /** @return ConexaoMcp[] */
    public function listar(): array;

    public function salvar(ConexaoMcp $conexao): ConexaoMcp;

    public function excluir(int $id): void;
}
