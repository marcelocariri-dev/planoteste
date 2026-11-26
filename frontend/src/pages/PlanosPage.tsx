import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { planoTesteApi } from '../api/planoTesteApi';
import { StatusBadge } from '../components/Badges';
import { Modal } from '../components/Modal';
import { PlanoForm } from '../components/PlanoForm';
import { ResumoBarra } from '../components/ResumoBarra';
import type { Plano } from '../types';

export function PlanosPage() {
  const [planos, setPlanos] = useState<Plano[]>([]);
  const [busca, setBusca] = useState('');
  const [status, setStatus] = useState('');
  const [carregando, setCarregando] = useState(true);
  const [erro, setErro] = useState('');
  const [novo, setNovo] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    const t = setTimeout(() => {
      setCarregando(true);
      planoTesteApi
        .listarPlanos(busca, status)
        .then((p) => { setPlanos(p); setErro(''); })
        .catch(() => setErro('Não foi possível carregar os planos. A API está rodando?'))
        .finally(() => setCarregando(false));
    }, 250);
    return () => clearTimeout(t);
  }, [busca, status]);

  return (
    <>
      <div className="topo-pagina">
        <h1>Planos de teste</h1>
        <button className="btn btn-primario" onClick={() => setNovo(true)}>+ Novo plano</button>
      </div>

      <div className="filtros">
        <input placeholder="Buscar por nome, projeto ou versão…" value={busca} onChange={(e) => setBusca(e.target.value)} />
        <select value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="">Todos os status</option>
          <option value="rascunho">Rascunho</option>
          <option value="em_execucao">Em execução</option>
          <option value="concluido">Concluído</option>
        </select>
      </div>

      {erro && <div className="alerta erro">{erro}</div>}

      {!carregando && !erro && planos.length === 0 && (
        <div className="vazio">
          <p>Nenhum plano encontrado.</p>
          <button className="btn btn-primario" onClick={() => setNovo(true)}>Criar o primeiro plano</button>
        </div>
      )}

      <div className="grade">
        {planos.map((p) => (
          <Link key={p.id} to={`/planos/${p.id}`} className="card">
            <div className="card-topo">
              <StatusBadge status={p.status} />
              {p.versao && <span className="muted">v{p.versao}</span>}
            </div>
            <h3>{p.nome}</h3>
            <p className="muted">{p.projeto ?? 'Sem projeto'}{p.responsavel && ` · ${p.responsavel}`}</p>
            {p.resumo && <ResumoBarra resumo={p.resumo} compacto />}
          </Link>
        ))}
      </div>

      {novo && (
        <Modal titulo="Novo plano de teste" onFechar={() => setNovo(false)}>
          <PlanoForm onCancelar={() => setNovo(false)} onSalvo={(p) => navigate(`/planos/${p.id}`)} />
        </Modal>
      )}
    </>
  );
}
