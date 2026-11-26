import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { mensagemErro } from '../api/client';
import { mcpApi } from '../api/produtoApi';
import type { BuscaCasosMcp, ConexaoMcp, FerramentaMcp, ResultadoImportacao } from '../types';
import { PrioridadeBadge } from './Badges';
import { DestinoPlano, type Destino } from './DestinoPlano';
import { ResultadoImportacaoAviso } from './ResultadoImportacaoAviso';

interface Props {
  conexoes: ConexaoMcp[];
  conexaoInicial?: number;
  planoIdFixo?: number; // quando aberto de dentro de um plano de teste
  onImportado?: (r: ResultadoImportacao) => void;
}

/** Busca os casos oficiais via MCP, mostra a prévia e importa os escolhidos para um plano. */
export function ImportadorMcp({ conexoes, conexaoInicial, planoIdFixo, onImportado }: Props) {
  const ativas = conexoes.filter((c) => c.ativo);
  const [conexaoId, setConexaoId] = useState<number>(conexaoInicial ?? ativas[0]?.id ?? 0);
  const conexao = ativas.find((c) => c.id === conexaoId);
  const [ferramentas, setFerramentas] = useState<FerramentaMcp[]>([]);
  const [ferramenta, setFerramenta] = useState('');
  const [args, setArgs] = useState<Record<string, unknown>>({});
  const [modoJson, setModoJson] = useState(false);
  const [argsJson, setArgsJson] = useState('{}');
  const [busca, setBusca] = useState<BuscaCasosMcp | null>(null);
  const [selecionados, setSelecionados] = useState<number[]>([]);
  const [aberto, setAberto] = useState<number | null>(null);
  const [destino, setDestino] = useState<Destino>(planoIdFixo ? { tipo: 'existente', planoId: planoIdFixo } : { tipo: 'novo', nome: '' });
  const [carregando, setCarregando] = useState<'' | 'ferramentas' | 'casos' | 'importar'>('');
  const [erro, setErro] = useState('');
  const [resultado, setResultado] = useState<ResultadoImportacao | null>(null);

  useEffect(() => {
    if (!conexao) return;
    setFerramentas([]);
    setFerramenta(conexao.ferramenta_casos ?? '');
    setArgs({ ...conexao.argumentos_padrao });
    setArgsJson(JSON.stringify(conexao.argumentos_padrao ?? {}, null, 2));
    setBusca(null);
    setResultado(null);
    if (!planoIdFixo) setDestino({ tipo: 'novo', nome: `Casos oficiais - ${conexao.nome}` });
  }, [conexaoId]); // eslint-disable-line react-hooks/exhaustive-deps

  const esquema = useMemo(() => ferramentas.find((f) => f.nome === ferramenta)?.esquema_entrada, [ferramentas, ferramenta]);

  async function executar<T>(tipo: typeof carregando, fn: () => Promise<T>): Promise<T | undefined> {
    setCarregando(tipo);
    setErro('');
    try {
      return await fn();
    } catch (e) {
      setErro(mensagemErro(e));
    } finally {
      setCarregando('');
    }
  }

  const carregarFerramentas = () =>
    executar('ferramentas', async () => {
      const t = await mcpApi.testar(conexaoId);
      setFerramentas(t.ferramentas);
      if (!ferramenta && t.ferramentas[0]) setFerramenta(t.ferramentas[0].nome);
    });

  const buscarCasos = () =>
    executar('casos', async () => {
      let argumentos = args;
      if (modoJson) {
        try { argumentos = JSON.parse(argsJson || '{}'); } catch { throw new Error('Argumentos: JSON inválido.'); }
      }
      const r = await mcpApi.buscarCasos(conexaoId, ferramenta || null, argumentos);
      setBusca(r);
      setSelecionados(r.casos.map((_, i) => i));
      setResultado(null);
    });

  const importar = () =>
    executar('importar', async () => {
      if (!busca) return;
      const casos = busca.casos.filter((_, i) => selecionados.includes(i));
      const r = await mcpApi.importar(
        conexaoId,
        casos,
        destino.tipo === 'existente' ? destino.planoId : null,
        destino.tipo === 'novo' ? { nome: destino.nome } : null,
      );
      setResultado(r);
      onImportado?.(r);
    });

  if (ativas.length === 0) {
    return <div className="alerta">Nenhuma conexão MCP ativa. <Link to="/integracao-mcp">Cadastrar conexão →</Link></div>;
  }

  const props = esquema?.properties ?? {};
  const alternar = (i: number) => setSelecionados((s) => (s.includes(i) ? s.filter((x) => x !== i) : [...s, i]));

  return (
    <div className="importador-mcp">
      <div className="painel form">
        <div className="linha">
          <label className="campo">
            <span>Conexão</span>
            <select value={conexaoId} onChange={(e) => setConexaoId(Number(e.target.value))}>
              {ativas.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
            </select>
          </label>
          <label className="campo">
            <span>Ferramenta que retorna os casos</span>
            <div className="linha-inline">
              {ferramentas.length > 0 ? (
                <select value={ferramenta} onChange={(e) => setFerramenta(e.target.value)}>
                  {ferramentas.map((f) => <option key={f.nome} value={f.nome}>{f.nome}</option>)}
                </select>
              ) : (
                <input value={ferramenta} onChange={(e) => setFerramenta(e.target.value)} placeholder="ex.: listar_casos" />
              )}
              <button className="btn" onClick={carregarFerramentas} disabled={carregando !== ''}>
                {carregando === 'ferramentas' ? 'Conectando…' : 'Carregar ferramentas'}
              </button>
            </div>
          </label>
        </div>
        {ferramentas.find((f) => f.nome === ferramenta)?.descricao && (
          <p className="muted pequeno">{ferramentas.find((f) => f.nome === ferramenta)?.descricao}</p>
        )}

        <div className="linha-entre">
          <strong className="pequeno">Argumentos</strong>
          <button className="btn btn-pequeno" onClick={() => { setModoJson(!modoJson); setArgsJson(JSON.stringify(args, null, 2)); }}>
            {modoJson ? 'Formulário' : 'JSON'}
          </button>
        </div>
        {modoJson || Object.keys(props).length === 0 ? (
          <textarea rows={4} className="mono" value={modoJson ? argsJson : JSON.stringify(args, null, 2)}
            onChange={(e) => { setModoJson(true); setArgsJson(e.target.value); }} />
        ) : (
          <div className="grade-args">
            {Object.entries(props).map(([nome, p]) => (
              <label key={nome} className="campo">
                <span>{nome}{esquema?.required?.includes(nome) && <em> *</em>}</span>
                {p.enum ? (
                  <select value={String(args[nome] ?? '')} onChange={(e) => setArgs({ ...args, [nome]: e.target.value })}>
                    <option value="" />
                    {p.enum.map((v) => <option key={v} value={v}>{v}</option>)}
                  </select>
                ) : p.type === 'boolean' ? (
                  <input type="checkbox" checked={!!args[nome]} onChange={(e) => setArgs({ ...args, [nome]: e.target.checked })} />
                ) : (
                  <input
                    type={p.type === 'number' || p.type === 'integer' ? 'number' : 'text'}
                    value={String(args[nome] ?? '')}
                    placeholder={p.description}
                    onChange={(e) => setArgs({ ...args, [nome]: p.type === 'number' || p.type === 'integer' ? Number(e.target.value) : e.target.value })}
                  />
                )}
              </label>
            ))}
          </div>
        )}
        <div className="form-acoes">
          <button className="btn btn-primario" onClick={buscarCasos} disabled={carregando !== '' || !ferramenta}>
            {carregando === 'casos' ? 'Buscando…' : 'Buscar casos oficiais'}
          </button>
        </div>
      </div>

      {erro && <div className="alerta erro">{erro}</div>}

      {busca && (
        <div className="painel">
          <div className="linha-entre">
            <h3>{busca.total} caso(s) retornado(s) por <code>{busca.ferramenta}</code></h3>
            <button className="btn btn-pequeno" onClick={() => setSelecionados(selecionados.length === busca.casos.length ? [] : busca.casos.map((_, i) => i))}>
              {selecionados.length === busca.casos.length ? 'Desmarcar todos' : 'Marcar todos'}
            </button>
          </div>
          <table className="tabela">
            <thead><tr><th style={{ width: 36 }} /><th style={{ width: 110 }}>ID oficial</th><th>Caso</th><th style={{ width: 100 }}>Prioridade</th></tr></thead>
            <tbody>
              {busca.casos.map((c, i) => (
                <tr key={i} className={selecionados.includes(i) ? '' : 'linha-apagada'}>
                  <td><input type="checkbox" checked={selecionados.includes(i)} onChange={() => alternar(i)} /></td>
                  <td className="mono pequeno">{c.id_externo ?? '—'}</td>
                  <td>
                    <button className="link" onClick={() => setAberto(aberto === i ? null : i)}>{c.titulo}</button>
                    {aberto === i && (
                      <div className="pequeno detalhe-caso">
                        {c.pre_condicoes && <p className="pre"><b>Pré-condições:</b> {c.pre_condicoes}</p>}
                        <p className="pre"><b>Passos:</b>{'\n'}{c.passos}</p>
                        <p className="pre"><b>Resultado esperado:</b> {c.resultado_esperado}</p>
                      </div>
                    )}
                  </td>
                  <td><PrioridadeBadge prioridade={c.prioridade} /></td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="importar-rodape">
            {!planoIdFixo && <DestinoPlano valor={destino} onChange={setDestino} nomeSugerido={`Casos oficiais - ${conexao?.nome ?? ''}`} />}
            {resultado && <ResultadoImportacaoAviso r={resultado} />}
            <button className="btn btn-primario" disabled={selecionados.length === 0 || carregando !== ''} onClick={importar}>
              {carregando === 'importar' ? 'Importando…' : `Importar ${selecionados.length} caso(s) para o plano`}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
