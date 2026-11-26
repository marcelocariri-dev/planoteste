import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { ApiError } from '../api/client';
import { planoTesteApi } from '../api/planoTesteApi';
import { PrioridadeBadge, ResultadoBadge, StatusBadge } from '../components/Badges';
import { CasoForm } from '../components/CasoForm';
import { ExecucaoPainel } from '../components/ExecucaoPainel';
import { Modal } from '../components/Modal';
import { PlanoForm } from '../components/PlanoForm';
import { ResumoBarra } from '../components/ResumoBarra';
import { ROTULO_RESULTADO, type Caso, type PlanoDetalhe, type ResultadoAtual } from '../types';

type ModalAberto =
  | { tipo: 'editarPlano' }
  | { tipo: 'novoCaso' }
  | { tipo: 'editarCaso'; caso: Caso }
  | { tipo: 'executar'; casoId: number }
  | null;

export function PlanoDetalhePage() {
  const id = Number(useParams().id);
  const navigate = useNavigate();
  const [plano, setPlano] = useState<PlanoDetalhe | null>(null);
  const [erro, setErro] = useState('');
  const [modal, setModal] = useState<ModalAberto>(null);
  const [filtro, setFiltro] = useState<ResultadoAtual | ''>('');

  const carregar = useCallback(
    () =>
      planoTesteApi
        .obterPlano(id)
        .then((p) => { setPlano(p); setErro(''); })
        .catch((e) => setErro(e instanceof ApiError ? e.message : 'Erro ao carregar o plano.')),
    [id],
  );

  useEffect(() => { carregar(); }, [carregar]);

  const acao = async (fn: () => Promise<unknown>, confirmar?: string) => {
    if (confirmar && !window.confirm(confirmar)) return;
    try {
      await fn();
      await carregar();
    } catch (e) {
      setErro(e instanceof ApiError ? e.message : 'Erro inesperado.');
    }
  };

  if (!plano) {
    return erro ? <div className="alerta erro">{erro} <Link to="/">Voltar</Link></div> : <p className="muted">Carregando…</p>;
  }

  const concluido = plano.status === 'concluido';
  const casos = filtro ? plano.casos.filter((c) => c.resultado_atual === filtro) : plano.casos;
  const casoExecutando = modal?.tipo === 'executar' ? plano.casos.find((c) => c.id === modal.casoId) : undefined;

  return (
    <>
      <Link to="/" className="voltar">← Planos</Link>

      <div className="topo-pagina">
        <div>
          <div className="linha-inline">
            <h1>{plano.nome}</h1>
            <StatusBadge status={plano.status} />
          </div>
          <p className="muted">
            {[plano.projeto, plano.versao && `v${plano.versao}`, plano.responsavel].filter(Boolean).join(' · ') || 'Sem detalhes'}
          </p>
        </div>
        <div className="acoes">
          {!concluido && <button className="btn" onClick={() => setModal({ tipo: 'editarPlano' })}>Editar</button>}
          {concluido ? (
            <button className="btn" onClick={() => acao(() => planoTesteApi.reabrirPlano(id))}>Reabrir</button>
          ) : (
            <button
              className="btn"
              onClick={() => acao(() => planoTesteApi.concluirPlano(id), 'Concluir o plano? Ele ficará bloqueado para edição.')}
            >
              Concluir
            </button>
          )}
          <button
            className="btn btn-perigo"
            onClick={() => {
              if (window.confirm('Excluir o plano e todos os casos e execuções?')) {
                planoTesteApi.excluirPlano(id).then(() => navigate('/'));
              }
            }}
          >
            Excluir
          </button>
        </div>
      </div>

      {plano.descricao && <p className="descricao pre">{plano.descricao}</p>}
      {erro && <div className="alerta erro">{erro}</div>}

      <ResumoBarra resumo={plano.resumo} />

      <div className="topo-secao">
        <h2>Casos de teste</h2>
        <div className="acoes">
          <select value={filtro} onChange={(e) => setFiltro(e.target.value as ResultadoAtual | '')}>
            <option value="">Todos</option>
            {(Object.keys(ROTULO_RESULTADO) as ResultadoAtual[]).map((r) => (
              <option key={r} value={r}>{ROTULO_RESULTADO[r]}</option>
            ))}
          </select>
          {!concluido && (
            <button className="btn btn-primario" onClick={() => setModal({ tipo: 'novoCaso' })}>+ Caso de teste</button>
          )}
        </div>
      </div>

      {plano.casos.length === 0 ? (
        <div className="vazio"><p>Este plano ainda não tem casos de teste.</p></div>
      ) : (
        <table className="tabela">
          <thead>
            <tr>
              <th style={{ width: 48 }}>#</th>
              <th>Título</th>
              <th style={{ width: 110 }}>Prioridade</th>
              <th style={{ width: 140 }}>Resultado</th>
              <th style={{ width: 220 }} />
            </tr>
          </thead>
          <tbody>
            {casos.map((c) => (
              <tr key={c.id}>
                <td className="muted">{c.ordem}</td>
                <td>
                  <button className="link" onClick={() => setModal({ tipo: 'executar', casoId: c.id })}>{c.titulo}</button>
                </td>
                <td><PrioridadeBadge prioridade={c.prioridade} /></td>
                <td><ResultadoBadge resultado={c.resultado_atual} /></td>
                <td className="acoes-linha">
                  <button className="btn btn-pequeno" onClick={() => setModal({ tipo: 'executar', casoId: c.id })}>
                    {concluido ? 'Ver' : 'Executar'}
                  </button>
                  {!concluido && (
                    <>
                      <button className="btn btn-pequeno" onClick={() => setModal({ tipo: 'editarCaso', caso: c })}>Editar</button>
                      <button
                        className="btn btn-pequeno btn-perigo"
                        onClick={() => acao(() => planoTesteApi.removerCaso(c.id), `Remover o caso "${c.titulo}"?`)}
                      >
                        Remover
                      </button>
                    </>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      {modal?.tipo === 'editarPlano' && (
        <Modal titulo="Editar plano" onFechar={() => setModal(null)}>
          <PlanoForm plano={plano} onCancelar={() => setModal(null)} onSalvo={() => { setModal(null); carregar(); }} />
        </Modal>
      )}
      {(modal?.tipo === 'novoCaso' || modal?.tipo === 'editarCaso') && (
        <Modal titulo={modal.tipo === 'novoCaso' ? 'Novo caso de teste' : 'Editar caso de teste'} onFechar={() => setModal(null)} largo>
          <CasoForm
            planoId={id}
            caso={modal.tipo === 'editarCaso' ? modal.caso : undefined}
            onCancelar={() => setModal(null)}
            onSalvo={() => { setModal(null); carregar(); }}
          />
        </Modal>
      )}
      {casoExecutando && (
        <Modal titulo={casoExecutando.titulo} onFechar={() => setModal(null)} largo>
          <ExecucaoPainel caso={casoExecutando} podeExecutar={!concluido} onRegistrado={carregar} />
        </Modal>
      )}
    </>
  );
}
