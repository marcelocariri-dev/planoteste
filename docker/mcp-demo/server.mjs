// Servidor MCP de DEMONSTRAÇÃO (transporte Streamable HTTP, sem dependências).
// Simula o sistema oficial da empresa expondo casos de teste por módulo.
// Ferramentas: listar_modulos, listar_casos { modulo, prioridade? }
import http from 'node:http';
import { randomUUID } from 'node:crypto';

const CASOS = {
  PDV: [
    { id: 'PDV-001', titulo: 'Venda à vista em dinheiro', prioridade: 'critica', pre_condicoes: 'Caixa aberto',
      passos: ['Incluir produto', 'Finalizar em dinheiro', 'Informar valor recebido'], resultado_esperado: 'NFC-e autorizada e troco correto' },
    { id: 'PDV-002', titulo: 'Venda com cartão (TEF)', prioridade: 'alta', pre_condicoes: 'Pinpad conectado',
      passos: ['Incluir produto', 'Finalizar com cartão de crédito'], resultado_esperado: 'Transação aprovada e comprovante impresso' },
    { id: 'PDV-003', titulo: 'Sangria de caixa', prioridade: 'media',
      passos: ['Menu > Sangria', 'Informar valor e motivo'], resultado_esperado: 'Sangria registrada e comprovante impresso' },
  ],
  FISCAL: [
    { id: 'FIS-010', titulo: 'Emissão de NF-e de venda', prioridade: 'critica', pre_condicoes: 'Certificado A1 válido',
      passos: ['Criar pedido', 'Faturar', 'Transmitir NF-e'], resultado_esperado: 'NF-e autorizada (cStat 100)' },
    { id: 'FIS-011', titulo: 'Carta de correção', prioridade: 'baixa',
      passos: ['Selecionar NF-e autorizada', 'Emitir CC-e'], resultado_esperado: 'Evento 110110 homologado' },
  ],
};

const TOOLS = [
  { name: 'listar_modulos', description: 'Lista os módulos que possuem casos oficiais',
    inputSchema: { type: 'object', properties: {} } },
  { name: 'listar_casos', description: 'Retorna os casos de teste oficiais de um módulo',
    inputSchema: { type: 'object', properties: {
      modulo: { type: 'string', enum: Object.keys(CASOS), description: 'Tag do módulo' },
      prioridade: { type: 'string', description: 'Filtrar por prioridade (opcional)' },
    }, required: ['modulo'] } },
];

function responder(id, result) { return { jsonrpc: '2.0', id, result }; }
function erro(id, code, message) { return { jsonrpc: '2.0', id, error: { code, message } }; }

function tratar(msg) {
  const { id, method, params = {} } = msg;
  switch (method) {
    case 'initialize':
      return responder(id, { protocolVersion: params.protocolVersion ?? '2025-06-18',
        capabilities: { tools: {} }, serverInfo: { name: 'mcp-demo-casos-oficiais', version: '1.0.0' } });
    case 'tools/list':
      return responder(id, { tools: TOOLS });
    case 'tools/call': {
      const args = params.arguments ?? {};
      if (params.name === 'listar_modulos') {
        return responder(id, { content: [{ type: 'text', text: JSON.stringify(Object.keys(CASOS)) }] });
      }
      if (params.name === 'listar_casos') {
        const lista = CASOS[String(args.modulo ?? '').toUpperCase()];
        if (!lista) return responder(id, { isError: true, content: [{ type: 'text', text: `Módulo "${args.modulo}" não encontrado` }] });
        const casos = args.prioridade ? lista.filter((c) => c.prioridade === args.prioridade) : lista;
        return responder(id, { content: [{ type: 'text', text: JSON.stringify({ casos }) }], structuredContent: { casos } });
      }
      return erro(id, -32602, `Ferramenta desconhecida: ${params.name}`);
    }
    case 'ping':
      return responder(id, {});
    default:
      return id === undefined ? null : erro(id, -32601, `Método não suportado: ${method}`);
  }
}

http.createServer((req, res) => {
  if (req.url !== '/mcp') { res.writeHead(404).end(); return; }
  if (req.method === 'DELETE') { res.writeHead(200).end(); return; }
  if (req.method !== 'POST') { res.writeHead(405).end(); return; }

  let corpo = '';
  req.on('data', (c) => (corpo += c));
  req.on('end', () => {
    let msg;
    try { msg = JSON.parse(corpo); } catch { res.writeHead(400).end(); return; }
    const resposta = tratar(msg);
    if (!resposta) { res.writeHead(202).end(); return; } // notificação
    const headers = { 'Content-Type': 'application/json' };
    if (msg.method === 'initialize') headers['Mcp-Session-Id'] = randomUUID();
    res.writeHead(200, headers).end(JSON.stringify(resposta));
  });
}).listen(8765, () => console.log('MCP demo em http://0.0.0.0:8765/mcp'));
