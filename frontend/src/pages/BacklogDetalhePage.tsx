import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { mensagemErro } from '../api/client';
import { backlogApi } from '../api/produtoApi';
import { DestinoPlano, type Destino } from '../components/DestinoPlano';
import { Modal } from '../components/Modal';
import { ResultadoImportacaoAviso } from '../components/ResultadoImportacaoAviso';
import { baixarTexto, copiar, formatarData } from '../lib/util';
import type { Backlog, ResultadoImportacao } from '../types';
import { StatusBacklogBadge } from './BacklogsPage';

function paraMarkdown(b: Backlog): string {
  const l: string[] = [`# ${b.titulo}`, '', `_Gerado em ${formatarData(b.criado_em)}${b.modelo ? ` · ${b.modelo}` : ''}_`, ''];
  for (const e of b.epicos) {
    l.push(`## ${e.codigo} - ${e.titulo}`, '');
    if (e.descricao) l.push(e.descricao, '');
    for (const h of e.historias) {
      l.push(`### ${h.codigo} - ${h.titulo}`, '');
      l.push(`**Como** ${h.como_um}, **quero** ${h.quero}, **para** ${h.para}.`, '');
      l.push(`- Prioridade: ${h.prioridade}${h.estimativa ? ` · Estimativa: ${h.estimativa} SP` : ''}`, '');
      if (h.criterios_aceite.length) l.push('**Critérios de aceite**', '', ...h.criterios_aceite.map((c) => `- ${c}`), '');
      if (h.observacoes) l.push(`> ${h.observacoes}`, '');
    }
  }
  return l.join('\n');
}

function paraCsv(b: Backlog): string {
  const esc = (v: unknown) => `"${String(v ?? '').replace(/"/g, '""')}"`;
  const linhas = [['Épico', 'Título do épico', 'História', 'Título', 'Como um', 'Quero', 'Para', 'Critérios de aceite', 'Prioridade', 'Story points', 'Observações']];
  b.epicos.forEach((e) => e.historias.forEach((h) =>
    linhas.push([e.codigo, e.titulo, h.codigo, h.titulo, h.como_um, h.quero, h.para, h.criterios_aceite.join('\n'), h.prioridade, String(h.estimativa ?? ''), h.observacoes ?? ''])));
  return '﻿' + linhas.map((l) => l.map(esc).join(';')).join('\r\n'); // BOM + ; para abrir certo no Excel pt-BR
}

export function BacklogDetalhePage() {
  const id = Number(useParams().id);
  const navigate = useNavigate();
  const [b, setB] = useState<Backlog | null>(null);
  const [resposta, setResposta] = useState('');
  const [erro, setErro] = useState('');
  const [copiado, setCopiado] = useState(false);
  const [verPrompt, setVerPrompt] = useState(false);
  const [selecionadas, setSelecionadas] = useState<string[]>([]);
  const [gerarPlano, setGerarPlano] = useState(false);
  const [destino, setDestino] = useState<Destino>({ tipo: 'novo', nome: '' });
  const [resultado, setResultado] = useState<ResultadoImportacao | null>(null);

  const carregar = useCallback(() => backlogApi.obter(id).then((x) => {
    setB(x);
    setSelecionadas(x.epicos.flatMap((e) => e.historias.map((h) => h.codigo)));
    setDestino({ tipo: 'novo', nome: `Backlog: ${x.titulo}` });
  }).catch((e) => setErro(mensagemErro(e))), [id]);

  useEffect(() => { carregar(); }, [carregar]);

  if (!b) return erro ? <div className="alerta erro">{erro}</div> : <p className="muted">Carregando…</p>;

  const importar = () => backlogApi.importarResposta(id, resposta).then((x) => { setB(x); setErro(''); setResposta(''); carregar(); }).catch((e) => setErro(mensagemErro(e)));
  const totalSP = b.epicos.flatMap((e) => e.historias).reduce((s, h) => s + (h.estimativa ?? 0), 0);
  const alternar = (c: string) => setSelecionadas((s) => (s.includes(c) ? s.filter((x) => x !== c) : [...s, c]));

  const levarParaPlano = async () => {
    try {
      const planoId = destino.tipo === 'existente' ? destino.planoId : null;
      setResultado(await backlogApi.gerarPlano(id, planoId, selecionadas));
      setErro('');
    } catch (e) {
      setErro(mensagemErro(e));
    }
  };

  return (
    <>
      <Link to="/backlog" className="voltar">← Product backlog</Link>
      <div className="topo-pagina">
        <div>
          <div className="linha-inline"><h1>{b.titulo}</h1><StatusBacklogBadge status={b.status} rotulo={b.status_rotulo} /></div>
          <p className="muted">
            {b.total_epicos} épico(s) · {b.total_historias} história(s){totalSP > 0 && ` · ${totalSP} story points`} · {formatarData(b.criado_em)}
            {b.modelo && ` · ${b.modelo}`}
          </p>
        </div>
        <div className="acoes">
          <button className="btn" onClick={() => setVerPrompt(true)}>Ver prompt</button>
          {b.status === 'gerado' && (
            <>
              <button className="btn" onClick={() => baixarTexto(`backlog-${b.id}.md`, paraMarkdown(b), 'text/markdown;charset=utf-8')}>Markdown</button>
              <button className="btn" onClick={() => baixarTexto(`backlog-${b.id}.csv`, paraCsv(b), 'text/csv;charset=utf-8')}>CSV (Excel/Jira)</button>
              <button className="btn btn-primario" onClick={() => setGerarPlano(true)}>Gerar plano de teste</button>
            </>
          )}
          <button className="btn btn-perigo" onClick={() => window.confirm('Excluir este backlog?') && backlogApi.excluir(id).then(() => navigate('/backlog'))}>Excluir</button>
        </div>
      </div>

      {erro && <div className="alerta erro">{erro}</div>}

      {b.status !== 'gerado' && (
        <div className="painel">
          {b.status === 'erro' && <div className="alerta erro"><b>Falha na geração:</b> {b.erro}</div>}
          <h3>{b.status === 'erro' ? 'Gerar manualmente' : 'Prompt pronto'}</h3>
          <ol className="pequeno">
            <li>Copie o prompt e cole numa conversa com o Claude.</li>
            {b.documento_ids.length > 0 && <li>Anexe na conversa os {b.documento_ids.length} documento(s) selecionado(s) do cofre.</li>}
            <li>Cole aqui a resposta (JSON) e clique em Importar.</li>
          </ol>
          <div className="linha-inline">
            <button className="btn" onClick={async () => { setCopiado(await copiar(b.prompt)); setTimeout(() => setCopiado(false), 2000); }}>
              {copiado ? '✓ Copiado' : 'Copiar prompt'}
            </button>
            <button className="btn" onClick={() => setVerPrompt(true)}>Ver prompt</button>
          </div>
          <textarea rows={8} className="mono" value={resposta} onChange={(e) => setResposta(e.target.value)} placeholder='{"epicos": [...]}' style={{ marginTop: 12 }} />
          <div className="form-acoes"><button className="btn btn-primario" disabled={!resposta.trim()} onClick={importar}>Importar resposta</button></div>
        </div>
      )}

      {b.epicos.map((e) => (
        <section key={e.codigo} className="epico">
          <header>
            <span className="codigo">{e.codigo}</span>
            <h2>{e.titulo}</h2>
            <span className="muted pequeno">{e.historias.length} história(s)</span>
          </header>
          {e.descricao && <p className="muted">{e.descricao}</p>}
          <div className="historias">
            {e.historias.map((h) => (
              <article key={h.codigo} className="historia">
                <div className="linha-entre">
                  <span className="codigo">{h.codigo}</span>
                  <span className="linha-inline">
                    <span className={`badge moscow-${h.prioridade.toLowerCase().replace(/[^a-z]/g, '')}`}>{h.prioridade}</span>
                    {h.estimativa !== null && <span className="badge status-rascunho">{h.estimativa} SP</span>}
                  </span>
                </div>
                <h3>{h.titulo}</h3>
                <p className="historia-frase">
                  <b>Como</b> {h.como_um}, <b>quero</b> {h.quero}, <b>para</b> {h.para}.
                </p>
                {h.criterios_aceite.length > 0 && (
                  <>
                    <h4>Critérios de aceite</h4>
                    <ul className="criterios">{h.criterios_aceite.map((c, i) => <li key={i}>{c}</li>)}</ul>
                  </>
                )}
                {h.observacoes && <p className="observacao pequeno">💬 {h.observacoes}</p>}
              </article>
            ))}
          </div>
        </section>
      ))}

      {verPrompt && (
        <Modal titulo="Prompt enviado / para enviar" onFechar={() => setVerPrompt(false)} largo>
          <h4>Orientações</h4>
          <p className="pre">{b.orientacoes}</p>
          <h4>Prompt completo</h4>
          <pre className="bloco-codigo">{b.prompt}</pre>
          {b.resposta_bruta && (<><h4>Resposta bruta</h4><pre className="bloco-codigo">{b.resposta_bruta}</pre></>)}
        </Modal>
      )}

      {gerarPlano && (
        <Modal titulo="Gerar plano de teste a partir das histórias" onFechar={() => { setGerarPlano(false); setResultado(null); }} largo>
          <p className="muted pequeno">Cada história vira um caso de teste: "Quando" vira passos e "Então" vira resultado esperado.</p>
          <ul className="lista-selecao altura-limitada">
            {b.epicos.flatMap((e) => e.historias.map((h) => (
              <li key={h.codigo}>
                <label>
                  <input type="checkbox" checked={selecionadas.includes(h.codigo)} onChange={() => alternar(h.codigo)} />
                  <span><b>{h.codigo}</b> {h.titulo} <span className="muted pequeno">({e.codigo})</span></span>
                </label>
              </li>
            )))}
          </ul>
          <DestinoPlano valor={destino} onChange={setDestino} nomeSugerido={`Backlog: ${b.titulo}`} />
          {resultado && <ResultadoImportacaoAviso r={resultado} />}
          <div className="form-acoes">
            <button className="btn btn-primario" disabled={selecionadas.length === 0} onClick={levarParaPlano}>Criar {selecionadas.length} caso(s)</button>
          </div>
        </Modal>
      )}
    </>
  );
}
