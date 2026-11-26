<?php

namespace Src\Application\IntegracaoMcp\Ports;

use Src\Domain\IntegracaoMcp\Entities\ConexaoMcp;
use Src\Domain\IntegracaoMcp\ValueObjects\FerramentaMcp;
use Src\Domain\IntegracaoMcp\ValueObjects\ResultadoFerramenta;

interface ClienteMcpInterface
{
    /**
     * Faz o handshake e lista as ferramentas do servidor.
     *
     * @return array{servidor: array{nome: ?string, versao: ?string}, ferramentas: FerramentaMcp[]}
     */
    public function listarFerramentas(ConexaoMcp $conexao): array;

    public function chamarFerramenta(ConexaoMcp $conexao, string $ferramenta, array $argumentos): ResultadoFerramenta;
}
