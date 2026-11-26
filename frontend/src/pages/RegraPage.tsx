import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { mensagemErro } from '../api/client';
import { moduloApi, regraApi } from '../api/produtoApi';
import { Abas } from '../components/Abas';
import { ModuloBadge } from '../components/ModuloBadge';
import { AnexosRegra, CasosRegra, ValidacaoRegra, VersoesRegra } from '../components/regra/AbasRegra';
import { EditorRegra } from '../components/regra/EditorRegra';
import { DecisaoBadge, StatusRegraBadge } from '../components/RegraBadges';
import { formatarData } from '../lib/util';
import type { AnaliseRegra, Modulo, Regra, TipoRegra } from '../types';

type AbaRegra = 'regra' | 'casos' | 'anexos' | 'validacao' | 'versoes';

export function RegraPage() {
  const id = Number(useParams().id);
  const navigate = useNavigate();
  const [regra, setRegra] = useState<Regra | null>(null);
  const [modulos, setModulos] = useState<Modulo[]>([]);
  const [form, setForm] = useState({ titulo: '', tipo: 'melhoria' as TipoRegra, modulo_id: 0, conteudo: '', autor: '' });
  const [analise, setAnalise] = useState<AnaliseRegra | null>(null);
  const [aba, setAba] = useState<AbaRegra>('regra');
  const [salvando, setSalvando] = useState(false);
  const [mensagem, setMensagem] = useState<{ tipo: 'erro' | 'sucesso'; texto: string } | null>(null);
  const salvarRef = useRef<() => void>(() => {});

  const aplicar = useCallback((r: Regra) => {
    setRegra(r);
    setForm({ titulo: r.titulo, tipo: r.tipo, modulo_id: r.modulo_id, conteudo: r.conteudo, autor: r.autor ?? '' });
    setAnalise({ marcadores: r.marcadores, contagem_marcadores: r.contagem_marcadores, casos_extraidos: r.casos_extraidos });
  }, []);

  const carregar = useCallback(() => regraApi.obter(id).then(aplicar).catch((e) => setMensagem({ tipo: 'erro', texto: mensagemErro(e) })), [id, aplicar]);

  useEffect(() => { carregar(); moduloApi.listar().then(setModulos); }, [carregar]);

  const alterado = !!regra && (form.conteudo !== regra.conteudo || form.titulo !== regra.titulo || form.tipo !== regra.tipo || form.modulo_id !== regra.modulo_id);
  const editavel = regra?.status === 'rascunho' || regra?.status === 'reprovada';

  // análise dos marcadores enquanto digita (usa a mesma regra do backend)
  useEffect(() => {
    if (!regra || form.conteudo === regra.conteudo) return;
    const t = setTimeout(() => regraApi.analisar(form.conteudo).then(setAnalise).catch(() => {}), 400);
    return () => clearTimeout(t);
  }, [form.conteudo, regra]);

  // avisa ao sair com alterações não salvas
  useEffect(() => {
    const aviso = (e: BeforeUnloadEvent) => { if (alterado) e.preventDefault(); };
    window.addEventListener('beforeunload', aviso);
    return () => window.removeEventListener('beforeunload', aviso);
  }, [alterado]);

  async function salvar() {
    if (!regra || !editavel || !alterado) return;
    setSalvando(true);
    setMensagem(null);
    try {
      aplicar(await regraApi.atualizar(regra.id, { ...form, autor: form.autor || null }));
      setMensagem({ tipo: 'sucesso', texto: 'Regra salva.' });
    } catch (e) {
      setMensagem({ tipo: 'erro', texto: mensagemErro(e) });
    } finally {
      setSalvando(false);
    }
  }
  salvarRef.current = salvar;

  useEffect(() => {
    const atalho = (e: KeyboardEvent) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        salvarRef.current();
      }
    };
    window.addEventListener('keydown', atalho);
    return () => window.removeEventListener('keydown', atalho);
  }, []);

  async function fluxo(acao: 'enviar' | 'retornar' | 'reabrir') {
    if (alterado && acao === 'enviar') {
      setMensagem({ tipo: 'erro', texto: 'Salve as alterações antes de enviar para validação.' });
      return;
    }
    try {
      aplicar(await regraApi.fluxo(id, acao));
      setMensagem(null);
      if (acao === 'enviar') setAba('validacao');
    } catch (e) {
      setMensagem({ tipo: 'erro', texto: mensagemErro(e) });
    }
  }

  if (!regra) return mensagem ? <div className="alerta erro">{mensagem.texto} <Link to="/regras">Voltar</Link></div> : <p className="muted">Carregando…</p>;

  const c = analise?.contagem_marcadores ?? regra.contagem_marcadores;

  return (
    <>
      <Link to="/regras" className="voltar">← Regras de negócio</Link>

      <div className="topo-pagina">
        <div className="cabecalho-regra">
          <input
            className="titulo-editavel"
            value={form.titulo}
            readOnly={!editavel}
            onChange={(e) => setForm({ ...form, titulo: e.target.value })}
          />
          <div className="linha-inline pequeno">
            <StatusRegraBadge status={regra.status} />
            <span className="muted">v{regra.versao}</span>
            {regra.validacoes.map((v) => <DecisaoBadge key={v.papel} papel={v.papel} decisao={v.decisao} />)}
            <select value={form.modulo_id} disabled={!editavel} onChange={(e) => setForm({ ...form, modulo_id: Number(e.target.value) })} className="select-compacto">
              {modulos.map((m) => <option key={m.id} value={m.id}>{m.tag}</option>)}
            </select>
            <select value={form.tipo} disabled={!editavel} onChange={(e) => setForm({ ...form, tipo: e.target.value as TipoRegra })} className="select-compacto">
              <option value="melhoria">Melhoria</option>
              <option value="novo_modulo">Novo módulo</option>
            </select>
            <input className="select-compacto" placeholder="Autor" value={form.autor} readOnly={!editavel} onChange={(e) => setForm({ ...form, autor: e.target.value })} />
            <span className="muted">· salva em {formatarData(regra.atualizado_em)}</span>
          </div>
        </div>
        <div className="acoes">
          {editavel && (
            <button className="btn btn-primario" disabled={!alterado || salvando} onClick={salvar} title="Ctrl+S">
              {salvando ? 'Salvando…' : alterado ? 'Salvar' : 'Salvo'}
            </button>
          )}
          {regra.status === 'rascunho' && <button className="btn" onClick={() => fluxo('enviar')}>Enviar para validação</button>}
          {(regra.status === 'em_validacao' || regra.status === 'reprovada') && <button className="btn" onClick={() => fluxo('retornar')}>Voltar para rascunho</button>}
          {regra.status === 'aprovada' && <button className="btn" onClick={() => fluxo('reabrir')}>Reabrir (nova versão)</button>}
          {regra.status !== 'aprovada' && (
            <button
              className="btn btn-perigo"
              onClick={() => window.confirm('Excluir a regra? Os documentos anexados continuam no cofre.') &&
                regraApi.excluir(id).then(() => navigate('/regras')).catch((e) => setMensagem({ tipo: 'erro', texto: mensagemErro(e) }))}
            >
              Excluir
            </button>
          )}
        </div>
      </div>

      {mensagem && <div className={`alerta ${mensagem.tipo}`}>{mensagem.texto}</div>}
      {!editavel && aba === 'regra' && (
        <div className="alerta">
          {regra.status === 'em_validacao' ? 'Em validação: o texto fica bloqueado até a decisão do PM/PO (ou volte para rascunho).' : 'Regra aprovada: somente leitura.'}
        </div>
      )}

      <Abas<AbaRegra>
        ativa={aba}
        onTrocar={setAba}
        abas={[
          { id: 'regra', rotulo: <>Regra de negócio{alterado && ' •'}</> },
          { id: 'casos', rotulo: `Casos marcados (${c.caso})` },
          { id: 'anexos', rotulo: `Anexos (${regra.total_documentos})` },
          { id: 'validacao', rotulo: 'Validação PM | PO' },
          { id: 'versoes', rotulo: 'Versões' },
        ]}
      />

      <div className="aba-conteudo">
        {aba === 'regra' && (
          <EditorRegra
            key={`${regra.id}-${editavel}`}
            valor={form.conteudo}
            onChange={(conteudo) => setForm((f) => ({ ...f, conteudo }))}
            somenteLeitura={!editavel}
            analise={analise}
          />
        )}
        {aba === 'casos' && <CasosRegra regra={regra} alteracoesPendentes={alterado} />}
        {aba === 'anexos' && <AnexosRegra regra={regra} onAlterado={carregar} />}
        {aba === 'validacao' && <ValidacaoRegra regra={regra} onAtualizado={aplicar} />}
        {aba === 'versoes' && <VersoesRegra regra={regra} />}
      </div>
      <div className="rodape-regra muted pequeno">
        <ModuloBadge modulo={regra.modulo} /> Dica: selecione um trecho no editor e use <b>Marcar como</b> para separar os testes em casos.
      </div>
    </>
  );
}
