import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { mensagemErro } from '../../api/client';
import { cofreApi, regraApi } from '../../api/produtoApi';
import { diffLinhas, formatarBytes, formatarData } from '../../lib/util';
import type { Categoria, Documento, Marcador, Regra, ResultadoImportacao, Validacao, VersaoRegra } from '../../types';
import { AreaUpload } from '../AreaUpload';
import { PrioridadeBadge } from '../Badges';
import { DestinoPlano, type Destino } from '../DestinoPlano';
import { ResultadoImportacaoAviso } from '../ResultadoImportacaoAviso';

// ------------------------------------------------------------------ Casos marcados
export function CasosRegra({ regra, alteracoesPendentes }: { regra: Regra; alteracoesPendentes: boolean }) {
  const [selecionados, setSelecionados] = useState<number[]>(() => regra.casos_extraidos.map((_, i) => i));
  const [destino, setDestino] = useState<Destino>({ tipo: 'novo', nome: `Regra: ${regra.titulo}` });
  const [resultado, setResultado] = useState<ResultadoImportacao | null>(null);
  const [erro, setErro] = useState('');
  const [gerando, setGerando] = useState(false);

  useEffect(() => setSelecionados(regra.casos_extraidos.map((_, i) => i)), [regra.casos_extraidos]);

  if (regra.casos_extraidos.length === 0) {
    return (
      <div className="vazio">
        <p>Nenhum trecho marcado como <strong>Caso</strong>.</p>
        <p className="pequeno">No editor, selecione o trecho que descreve um cenário e clique em <strong>🧪 Caso</strong>.</p>
      </div>
    );
  }

  const alternar = (i: number) => setSelecionados((s) => (s.includes(i) ? s.filter((x) => x !== i) : [...s, i]));

  const gerar = async () => {
    setErro('');
    setGerando(true);
    try {
      const planoId = destino.tipo === 'existente' ? destino.planoId : null;
      if (destino.tipo === 'existente' && !planoId) throw new Error('Escolha o plano de destino.');
      setResultado(await regraApi.gerarPlano(regra.id, planoId, selecionados));
    } catch (e) {
      setErro(e instanceof Error && !('status' in e) ? e.message : mensagemErro(e));
    } finally {
      setGerando(false);
    }
  };

  return (
    <div className="grade-2-1">
      <div className="lista-casos">
        {alteracoesPendentes && <div className="alerta">Há alterações não salvas no editor. Salve para gerar os casos com o texto atual.</div>}
        {regra.casos_extraidos.map((c, i) => (
          <label key={i} className={`caso-candidato ${selecionados.includes(i) ? 'selecionado' : ''}`}>
            <input type="checkbox" checked={selecionados.includes(i)} onChange={() => alternar(i)} />
            <div>
              <div className="linha-inline"><strong>{c.titulo}</strong><PrioridadeBadge prioridade={c.prioridade} /></div>
              {c.pre_condicoes && <p className="pre pequeno"><b>Pré-condições:</b> {c.pre_condicoes}</p>}
              <p className="pre pequeno"><b>Passos:</b>{'\n'}{c.passos}</p>
              <p className="pre pequeno"><b>Resultado esperado:</b> {c.resultado_esperado}</p>
            </div>
          </label>
        ))}
      </div>
      <div className="painel">
        <h3>Gerar plano de teste</h3>
        <p className="muted pequeno">
          {selecionados.length} de {regra.casos_extraidos.length} caso(s) selecionado(s). Casos que já existem no plano (mesmo título desta regra) não são duplicados.
        </p>
        <DestinoPlano valor={destino} onChange={setDestino} nomeSugerido={`Regra: ${regra.titulo}`} />
        {erro && <div className="alerta erro">{erro}</div>}
        {resultado && <ResultadoImportacaoAviso r={resultado} />}
        <button className="btn btn-primario largura-total" disabled={gerando || selecionados.length === 0} onClick={gerar}>
          {gerando ? 'Gerando…' : `Levar ${selecionados.length} caso(s) para o plano`}
        </button>
      </div>
    </div>
  );
}

// ------------------------------------------------------------------ Anexos -> cofre
export function AnexosRegra({ regra, onAlterado }: { regra: Regra; onAlterado: () => void }) {
  const [docs, setDocs] = useState<Documento[]>([]);
  const [categorias, setCategorias] = useState<Categoria[]>([]);
  const [categoriaId, setCategoriaId] = useState<number>(0);
  const [enviando, setEnviando] = useState<string[]>([]);
  const [erros, setErros] = useState<string[]>([]);

  const carregar = () => cofreApi.listar({ regra_id: regra.id }).then(setDocs);

  useEffect(() => {
    carregar();
    cofreApi.categorias().then((c) => {
      setCategorias(c);
      setCategoriaId((c.find((x) => /regra/i.test(x.nome)) ?? c[0])?.id ?? 0);
    });
  }, [regra.id]); // eslint-disable-line react-hooks/exhaustive-deps

  async function enviar(arquivos: File[]) {
    setErros([]);
    for (const arquivo of arquivos) {
      setEnviando((e) => [...e, arquivo.name]);
      try {
        await cofreApi.enviar(arquivo, { categoria_id: categoriaId, regra_id: regra.id, enviado_por: regra.autor });
      } catch (e) {
        setErros((x) => [...x, `${arquivo.name}: ${mensagemErro(e)}`]);
      } finally {
        setEnviando((e) => e.filter((n) => n !== arquivo.name));
      }
    }
    await carregar();
    onAlterado();
  }

  return (
    <div className="grade-2-1">
      <div>
        {docs.length === 0 ? (
          <div className="vazio"><p>Nenhum documento anexado a esta regra.</p></div>
        ) : (
          <table className="tabela">
            <thead><tr><th>Documento</th><th style={{ width: 160 }}>Categoria</th><th style={{ width: 90 }}>Tamanho</th><th style={{ width: 140 }} /></tr></thead>
            <tbody>
              {docs.map((d) => (
                <tr key={d.id}>
                  <td>
                    <strong>{d.titulo}</strong>
                    <div className="muted pequeno">{d.arquivo.nome_original} · {formatarData(d.criado_em)}</div>
                  </td>
                  <td>{categorias.find((c) => c.id === d.categoria_id)?.nome}</td>
                  <td className="muted">{formatarBytes(d.arquivo.tamanho)}</td>
                  <td className="acoes-linha">
                    <a className="btn btn-pequeno" href={cofreApi.urlDownload(d.id, true)} target="_blank" rel="noreferrer">Abrir</a>
                    <a className="btn btn-pequeno" href={cofreApi.urlDownload(d.id)}>Baixar</a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
      <div className="painel">
        <h3>Anexar documentos</h3>
        <p className="muted pequeno">
          Os arquivos vão para o <Link to={`/cofre?modulo=${regra.modulo_id}`}>cofre</Link>, departamentalizados pela tag <strong>{regra.modulo?.tag}</strong>.
        </p>
        <label className="campo">
          <span>Categoria</span>
          <select value={categoriaId} onChange={(e) => setCategoriaId(Number(e.target.value))}>
            {categorias.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
          </select>
        </label>
        <AreaUpload onArquivos={enviar} desabilitado={!categoriaId || enviando.length > 0} />
        {enviando.map((n) => <div key={n} className="muted pequeno">Enviando {n}…</div>)}
        {erros.map((e) => <div key={e} className="alerta erro">{e}</div>)}
      </div>
    </div>
  );
}

// ------------------------------------------------------------------ Validação PM / PO
function CartaoValidacao({ regra, v, onAtualizado }: { regra: Regra; v: Validacao; onAtualizado: (r: Regra) => void }) {
  const chave = `plano-teste:validador:${v.papel}`;
  const [responsavel, setResponsavel] = useState(() => {
    try { return localStorage.getItem(chave) ?? ''; } catch { return ''; }
  });
  const [parecer, setParecer] = useState('');
  const [erro, setErro] = useState('');
  const podeDecidir = regra.status === 'em_validacao';

  const decidir = async (decisao: 'aprovado' | 'reprovado') => {
    setErro('');
    try {
      const r = await regraApi.parecer(regra.id, { papel: v.papel, decisao, responsavel, parecer: parecer || null });
      try { localStorage.setItem(chave, responsavel); } catch { /* ignore */ }
      setParecer('');
      onAtualizado(r);
    } catch (e) {
      setErro(mensagemErro(e));
    }
  };

  return (
    <div className={`cartao-validacao decisao-borda-${v.decisao}`}>
      <header>
        <h3>{v.papel.toUpperCase()} · {v.papel === 'pm' ? 'Product Manager' : 'Product Owner'}</h3>
        <span className={`badge decisao-${v.decisao}`}>{v.decisao_rotulo}</span>
      </header>
      {v.decisao !== 'pendente' && (
        <div className="pequeno">
          <div><b>{v.responsavel}</b> <span className="muted">em {formatarData(v.em)}</span></div>
          {v.parecer && <p className="pre parecer">{v.parecer}</p>}
        </div>
      )}
      {podeDecidir && (
        <div className="form">
          <input placeholder={`Nome do ${v.papel.toUpperCase()}`} value={responsavel} onChange={(e) => setResponsavel(e.target.value)} />
          <textarea rows={3} placeholder="Parecer (obrigatório para reprovar)" value={parecer} onChange={(e) => setParecer(e.target.value)} />
          {erro && <div className="alerta erro">{erro}</div>}
          <div className="form-acoes">
            <button className="btn btn-perigo" onClick={() => decidir('reprovado')}>✗ Reprovar</button>
            <button className="btn btn-sucesso" onClick={() => decidir('aprovado')}>✓ Aprovar</button>
          </div>
        </div>
      )}
    </div>
  );
}

export function ValidacaoRegra({ regra, onAtualizado }: { regra: Regra; onAtualizado: (r: Regra) => void }) {
  const duvidas = regra.marcadores.filter((m: Marcador) => m.tipo === 'duvida');

  return (
    <>
      {regra.status === 'rascunho' && <div className="alerta">A regra está em rascunho. Envie para validação para que PM e PO registrem o parecer.</div>}
      {regra.status === 'aprovada' && <div className="alerta sucesso">Regra aprovada por PM e PO. Para alterar, reabra a regra (gera nova versão).</div>}
      {regra.status === 'reprovada' && <div className="alerta erro">Regra reprovada. Ajuste o texto no editor: ao salvar ela volta para rascunho.</div>}
      <div className="grade-2">
        {regra.validacoes.map((v) => <CartaoValidacao key={v.papel} regra={regra} v={v} onAtualizado={onAtualizado} />)}
      </div>
      {duvidas.length > 0 && (
        <div className="painel" style={{ marginTop: 16 }}>
          <h3>❓ Dúvidas em aberto no documento ({duvidas.length})</h3>
          <ul className="lista-simples">
            {duvidas.map((d, i) => <li key={i}><b>{d.titulo}</b>{d.conteudo && d.conteudo !== d.titulo && <p className="pre pequeno muted">{d.conteudo}</p>}</li>)}
          </ul>
        </div>
      )}
    </>
  );
}

// ------------------------------------------------------------------ Versões
export function VersoesRegra({ regra }: { regra: Regra }) {
  const [versoes, setVersoes] = useState<VersaoRegra[]>([]);
  const [sel, setSel] = useState<VersaoRegra | null>(null);

  useEffect(() => {
    regraApi.versoes(regra.id).then((v) => {
      setVersoes(v);
      setSel(v[1] ?? v[0] ?? null);
    });
  }, [regra.id, regra.versao]);

  const diff = sel ? diffLinhas(sel.conteudo, regra.conteudo) : [];

  return (
    <div className="grade-1-3">
      <ul className="lista-versoes">
        {versoes.map((v) => (
          <li key={v.versao} className={sel?.versao === v.versao ? 'ativa' : ''} onClick={() => setSel(v)}>
            <strong>v{v.versao}</strong> {v.versao === regra.versao && <span className="badge status-em_execucao">atual</span>}
            <div className="muted pequeno">{formatarData(v.criado_em)}{v.autor && ` · ${v.autor}`}</div>
          </li>
        ))}
      </ul>
      <div>
        {sel && (
          <>
            <p className="muted pequeno">Diferenças da <b>v{sel.versao}</b> para a versão atual (<b>v{regra.versao}</b>):</p>
            <pre className="diff">
              {diff.map((l, i) => (
                <div key={i} className={`diff-${l.tipo === '+' ? 'add' : l.tipo === '-' ? 'del' : 'eq'}`}>
                  {l.tipo === '=' ? ' ' : l.tipo} {l.texto}
                </div>
              ))}
            </pre>
          </>
        )}
      </div>
    </div>
  );
}
