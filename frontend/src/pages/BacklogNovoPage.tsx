import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { backlogApi, cofreApi, moduloApi, regraApi } from '../api/produtoApi';
import { Campo } from '../components/Campo';
import { Modal } from '../components/Modal';
import { PadroesBacklog } from '../components/PadroesBacklog';
import { StatusRegraBadge } from '../components/RegraBadges';
import { useFormulario } from '../components/useFormulario';
import { formatarBytes } from '../lib/util';
import type { Documento, Modulo, PadraoBacklog, RegraResumo } from '../types';

export function BacklogNovoPage() {
  const navigate = useNavigate();
  const [modulos, setModulos] = useState<Modulo[]>([]);
  const [padroes, setPadroes] = useState<PadraoBacklog[]>([]);
  const [regras, setRegras] = useState<RegraResumo[]>([]);
  const [docs, setDocs] = useState<Documento[]>([]);
  const [ia, setIa] = useState(false);
  const [editarPadroes, setEditarPadroes] = useState(false);
  const [d, setD] = useState({ titulo: '', modulo_id: '' as number | '', padrao_id: 0, orientacoes: '', regra_ids: [] as number[], documento_ids: [] as number[] });
  const { salvando, erros, mensagem, enviar } = useFormulario();

  const carregarPadroes = () => backlogApi.padroes().then((p) => {
    setPadroes(p);
    setD((x) => ({ ...x, padrao_id: p.some((y) => y.id === x.padrao_id) ? x.padrao_id : p[0]?.id ?? 0 }));
  });

  useEffect(() => {
    moduloApi.listar().then(setModulos);
    backlogApi.configuracao().then((c) => setIa(c.ia_disponivel));
    carregarPadroes();
  }, []);

  // regras e documentos do módulo escolhido (ou todos)
  useEffect(() => {
    regraApi.listar({ modulo_id: d.modulo_id }).then(setRegras);
    cofreApi.listar({ modulo_id: d.modulo_id }).then(setDocs);
  }, [d.modulo_id]);

  const alternar = (campo: 'regra_ids' | 'documento_ids', id: number) =>
    setD((x) => ({ ...x, [campo]: x[campo].includes(id) ? x[campo].filter((i) => i !== id) : [...x[campo], id] }));

  const gerar = () =>
    enviar(async () => {
      const b = await backlogApi.gerar({ ...d, modulo_id: d.modulo_id || null });
      navigate(`/backlog/${b.id}`);
    });

  const padrao = padroes.find((p) => p.id === d.padrao_id);

  return (
    <>
      <Link to="/backlog" className="voltar">← Product backlog</Link>
      <div className="topo-pagina">
        <div>
          <h1>Gerar product backlog</h1>
          <p className="muted">{ia ? 'A geração usa a API do Claude e pode levar até 1–2 minutos.' : 'Sem API configurada: o sistema monta o prompt para você usar no Claude.'}</p>
        </div>
      </div>

      {mensagem && <div className="alerta erro">{mensagem}</div>}

      <div className="grade-2-1">
        <div className="form painel">
          <div className="linha">
            <Campo rotulo="Título do backlog" obrigatorio erro={erros.titulo}>
              <input value={d.titulo} onChange={(e) => setD({ ...d, titulo: e.target.value })} placeholder="Ex.: Programa de cashback - PDV" />
            </Campo>
            <Campo rotulo="Módulo">
              <select value={d.modulo_id} onChange={(e) => setD({ ...d, modulo_id: Number(e.target.value) || '', regra_ids: [], documento_ids: [] })}>
                <option value="">Todos / não se aplica</option>
                {modulos.map((m) => <option key={m.id} value={m.id}>{m.tag} · {m.nome}</option>)}
              </select>
            </Campo>
          </div>
          <Campo rotulo="Padrão" obrigatorio erro={erros.padrao_id}>
            <div className="linha-inline">
              <select value={d.padrao_id} onChange={(e) => setD({ ...d, padrao_id: Number(e.target.value) })}>
                {padroes.map((p) => <option key={p.id} value={p.id}>{p.nome}</option>)}
              </select>
              <button type="button" className="btn" onClick={() => setEditarPadroes(true)}>Editar padrões</button>
            </div>
          </Campo>
          {padrao?.descricao && <p className="muted pequeno">{padrao.descricao}</p>}
          <Campo rotulo="Orientações do PM/PO" obrigatorio erro={erros.orientacoes}>
            <textarea
              rows={10}
              value={d.orientacoes}
              onChange={(e) => setD({ ...d, orientacoes: e.target.value })}
              placeholder={'Objetivo, público, restrições, prazos, o que é MVP, integrações, o que NÃO entra…'}
            />
          </Campo>
          <footer className="form-acoes">
            <button className="btn btn-primario" disabled={salvando} onClick={gerar}>
              {salvando ? (ia ? 'Gerando com o Claude… aguarde' : 'Montando prompt…') : ia ? '✨ Gerar backlog' : 'Montar prompt'}
            </button>
          </footer>
        </div>

        <div className="coluna">
          <div className="painel">
            <h3>Regras de negócio ({d.regra_ids.length})</h3>
            {regras.length === 0 && <p className="muted pequeno">Nenhuma regra neste módulo.</p>}
            <ul className="lista-selecao">
              {regras.map((r) => (
                <li key={r.id}>
                  <label>
                    <input type="checkbox" checked={d.regra_ids.includes(r.id)} onChange={() => alternar('regra_ids', r.id)} />
                    <span>{r.titulo} <span className="muted pequeno">v{r.versao}</span></span>
                    <StatusRegraBadge status={r.status} />
                  </label>
                </li>
              ))}
            </ul>
          </div>
          <div className="painel">
            <h3>Documentos do cofre ({d.documento_ids.length})</h3>
            {docs.length === 0 && <p className="muted pequeno">Nenhum documento neste módulo. <Link to="/cofre">Abrir o cofre</Link></p>}
            <ul className="lista-selecao">
              {docs.map((doc) => (
                <li key={doc.id}>
                  <label>
                    <input type="checkbox" checked={d.documento_ids.includes(doc.id)} onChange={() => alternar('documento_ids', doc.id)} />
                    <span>{doc.titulo} <span className="muted pequeno">.{doc.arquivo.extensao} · {formatarBytes(doc.arquivo.tamanho)}</span></span>
                  </label>
                </li>
              ))}
            </ul>
            <p className="muted pequeno">PDF, imagens, .docx e textos são enviados à IA. Planilhas e outros formatos são ignorados.</p>
          </div>
        </div>
      </div>

      {editarPadroes && (
        <Modal titulo="Padrões de backlog" onFechar={() => setEditarPadroes(false)} largo>
          <PadroesBacklog padroes={padroes} onAlterado={carregarPadroes} />
        </Modal>
      )}
    </>
  );
}
