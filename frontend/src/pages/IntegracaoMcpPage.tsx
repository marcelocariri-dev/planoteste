import { useEffect, useState } from 'react';
import { mensagemErro } from '../api/client';
import { mcpApi, type ConexaoMcpInput } from '../api/produtoApi';
import { Campo } from '../components/Campo';
import { ImportadorMcp } from '../components/ImportadorMcp';
import { Modal } from '../components/Modal';
import { useFormulario } from '../components/useFormulario';
import { formatarData } from '../lib/util';
import type { ConexaoMcp } from '../types';

function ConexaoForm({ conexao, onSalvo }: { conexao?: ConexaoMcp; onSalvo: () => void }) {
  const temCabecalhos = conexao && !Array.isArray(conexao.cabecalhos) && Object.keys(conexao.cabecalhos).length > 0;
  const [d, setD] = useState({
    nome: conexao?.nome ?? '',
    url: conexao?.url ?? '',
    token: '',
    cabecalhos: '',
    ferramenta_casos: conexao?.ferramenta_casos ?? '',
    argumentos_padrao: JSON.stringify(conexao?.argumentos_padrao ?? {}, null, 2),
    ativo: conexao?.ativo ?? true,
  });
  const [alterarToken, setAlterarToken] = useState(!conexao);
  const [alterarCabecalhos, setAlterarCabecalhos] = useState(!temCabecalhos);
  const { salvando, erros, mensagem, enviar } = useFormulario();

  const salvar = () =>
    enviar(async () => {
      const dados: ConexaoMcpInput = { nome: d.nome, url: d.url, ferramenta_casos: d.ferramenta_casos || null, argumentos_padrao: d.argumentos_padrao, ativo: d.ativo };
      if (alterarToken) dados.token = d.token;
      if (alterarCabecalhos) dados.cabecalhos = d.cabecalhos;
      await mcpApi.salvar(conexao?.id ?? null, dados);
      onSalvo();
    });

  return (
    <div className="form">
      {mensagem && <div className="alerta erro">{mensagem}</div>}
      <div className="linha">
        <Campo rotulo="Nome" obrigatorio erro={erros.nome}><input value={d.nome} onChange={(e) => setD({ ...d, nome: e.target.value })} placeholder="Ex.: QA oficial" /></Campo>
        <Campo rotulo="Ferramenta padrão dos casos" erro={erros.ferramenta_casos}>
          <input value={d.ferramenta_casos} onChange={(e) => setD({ ...d, ferramenta_casos: e.target.value })} placeholder="ex.: listar_casos" />
        </Campo>
      </div>
      <Campo rotulo="URL do servidor MCP (Streamable HTTP)" obrigatorio erro={erros.url}>
        <input value={d.url} onChange={(e) => setD({ ...d, url: e.target.value })} placeholder="https://mcp.empresa.com.br/mcp" />
      </Campo>
      <Campo rotulo="Token (Bearer)" erro={erros.token}>
        {alterarToken ? (
          <input type="password" value={d.token} onChange={(e) => setD({ ...d, token: e.target.value })} placeholder={conexao ? 'Vazio remove o token' : 'Opcional'} autoComplete="new-password" />
        ) : (
          <div className="linha-inline">
            <span className="muted">{conexao?.possui_token ? '•••••••• (guardado criptografado)' : 'Sem token'}</span>
            <button type="button" className="btn btn-pequeno" onClick={() => setAlterarToken(true)}>Alterar</button>
          </div>
        )}
      </Campo>
      <Campo rotulo="Cabeçalhos extras (JSON)" erro={erros.cabecalhos}>
        {alterarCabecalhos ? (
          <textarea rows={2} className="mono" value={d.cabecalhos} onChange={(e) => setD({ ...d, cabecalhos: e.target.value })} placeholder='{"X-Api-Key": "..."}' />
        ) : (
          <div className="linha-inline">
            <span className="muted">{Object.keys(conexao!.cabecalhos).join(', ')} (ocultos)</span>
            <button type="button" className="btn btn-pequeno" onClick={() => setAlterarCabecalhos(true)}>Alterar</button>
          </div>
        )}
      </Campo>
      <Campo rotulo="Argumentos padrão da ferramenta (JSON)" erro={erros.argumentos_padrao}>
        <textarea rows={3} className="mono" value={d.argumentos_padrao} onChange={(e) => setD({ ...d, argumentos_padrao: e.target.value })} />
      </Campo>
      <label className="radio"><input type="checkbox" checked={d.ativo} onChange={(e) => setD({ ...d, ativo: e.target.checked })} /> Conexão ativa</label>
      <footer className="form-acoes">
        <button className="btn btn-primario" disabled={salvando} onClick={salvar}>{salvando ? 'Salvando…' : 'Salvar conexão'}</button>
      </footer>
    </div>
  );
}

export function IntegracaoMcpPage() {
  const [conexoes, setConexoes] = useState<ConexaoMcp[]>([]);
  const [sel, setSel] = useState<number | null>(null);
  const [modal, setModal] = useState<ConexaoMcp | 'nova' | null>(null);
  const [erro, setErro] = useState('');
  const [testando, setTestando] = useState(false);

  const carregar = () => mcpApi.listar().then((c) => { setConexoes(c); setSel((s) => s ?? c[0]?.id ?? null); });
  useEffect(() => { carregar(); }, []);

  const conexao = conexoes.find((c) => c.id === sel);

  const testar = async () => {
    if (!conexao) return;
    setTestando(true);
    setErro('');
    try { await mcpApi.testar(conexao.id); } catch (e) { setErro(mensagemErro(e)); }
    await carregar();
    setTestando(false);
  };

  return (
    <>
      <div className="topo-pagina">
        <div>
          <h1>Integração MCP</h1>
          <p className="muted">Conecte ao servidor MCP oficial da empresa para consumir os casos de teste e levá-los para os planos.</p>
        </div>
        <button className="btn btn-primario" onClick={() => setModal('nova')}>+ Nova conexão</button>
      </div>

      {conexoes.length === 0 ? (
        <div className="vazio">
          <p>Nenhuma conexão cadastrada.</p>
          <p className="pequeno">Para testar localmente: <code>docker compose --profile demo up -d</code> e cadastre a URL <code>http://plano-teste-mcp-demo:8765/mcp</code> com a ferramenta <code>listar_casos</code>.</p>
        </div>
      ) : (
        <div className="cofre">
          <aside className="cofre-modulos">
            {conexoes.map((c) => (
              <button key={c.id} className={sel === c.id ? 'ativo' : ''} onClick={() => setSel(c.id)}>
                <span>
                  <i className="bolinha" style={{ background: c.ultimo_teste_ok === null ? '#9aa3ad' : c.ultimo_teste_ok ? '#16a34a' : '#dc2626' }} /> {c.nome}
                </span>
                {!c.ativo && <span className="muted pequeno">inativa</span>}
              </button>
            ))}
          </aside>

          {conexao && (
            <section>
              <div className="painel">
                <div className="linha-entre">
                  <div>
                    <h3>{conexao.nome}</h3>
                    <div className="mono pequeno muted">{conexao.url}</div>
                    <div className="pequeno">
                      {conexao.ultimo_teste_em ? (
                        <span className={conexao.ultimo_teste_ok ? 'txt-passou' : 'erro'}>
                          {conexao.ultimo_teste_ok ? '● ' : '● '}{conexao.ultimo_teste_mensagem} <span className="muted">({formatarData(conexao.ultimo_teste_em)})</span>
                        </span>
                      ) : <span className="muted">Conexão ainda não testada.</span>}
                    </div>
                  </div>
                  <div className="acoes">
                    <button className="btn" onClick={testar} disabled={testando}>{testando ? 'Testando…' : 'Testar conexão'}</button>
                    <button className="btn" onClick={() => setModal(conexao)}>Editar</button>
                    <button className="btn btn-perigo" onClick={() => window.confirm('Excluir a conexão?') && mcpApi.excluir(conexao.id).then(() => { setSel(null); carregar(); })}>Excluir</button>
                  </div>
                </div>
                {erro && <div className="alerta erro" style={{ marginTop: 12 }}>{erro}</div>}
              </div>

              <h2 style={{ margin: '20px 0 10px' }}>Consumir casos oficiais</h2>
              <ImportadorMcp key={conexao.id} conexoes={conexoes} conexaoInicial={conexao.id} />
            </section>
          )}
        </div>
      )}

      {modal && (
        <Modal titulo={modal === 'nova' ? 'Nova conexão MCP' : 'Editar conexão MCP'} onFechar={() => setModal(null)} largo>
          <ConexaoForm conexao={modal === 'nova' ? undefined : modal} onSalvo={() => { setModal(null); carregar(); }} />
        </Modal>
      )}
    </>
  );
}
