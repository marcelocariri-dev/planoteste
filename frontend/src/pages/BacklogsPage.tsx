import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { backlogApi, moduloApi } from '../api/produtoApi';
import { ModuloBadge } from '../components/ModuloBadge';
import { formatarData } from '../lib/util';
import type { BacklogResumo, Modulo } from '../types';

export const StatusBacklogBadge = ({ status, rotulo }: { status: string; rotulo: string }) => (
  <span className={`badge backlog-${status}`}>{rotulo}</span>
);

export function BacklogsPage() {
  const [backlogs, setBacklogs] = useState<BacklogResumo[]>([]);
  const [modulos, setModulos] = useState<Modulo[]>([]);
  const [ia, setIa] = useState<boolean | null>(null);

  useEffect(() => {
    backlogApi.listar().then(setBacklogs);
    moduloApi.listar().then(setModulos);
    backlogApi.configuracao().then((c) => setIa(c.ia_disponivel));
  }, []);

  return (
    <>
      <div className="topo-pagina">
        <div>
          <h1>Product backlog</h1>
          <p className="muted">Gera épicos, histórias e critérios de aceite a partir das orientações, regras validadas e documentos do cofre.</p>
        </div>
        <Link to="/backlog/novo" className="btn btn-primario">+ Gerar backlog</Link>
      </div>

      {ia === false && (
        <div className="alerta">
          IA não configurada: o sistema monta o prompt no padrão para você colar no Claude e importar a resposta.
          Para gerar direto, defina <code>ANTHROPIC_API_KEY</code> no <code>.env</code> da raiz e reinicie o container da API.
        </div>
      )}

      {backlogs.length === 0 ? (
        <div className="vazio"><p>Nenhum backlog gerado ainda.</p><Link to="/backlog/novo" className="btn btn-primario">Gerar o primeiro</Link></div>
      ) : (
        <div className="grade">
          {backlogs.map((b) => (
            <Link key={b.id} to={`/backlog/${b.id}`} className="card">
              <div className="card-topo">
                <StatusBacklogBadge status={b.status} rotulo={b.status_rotulo} />
                <ModuloBadge modulo={modulos.find((m) => m.id === b.modulo_id)} />
              </div>
              <h3>{b.titulo}</h3>
              <p className="muted pequeno">
                {b.total_epicos} épico(s) · {b.total_historias} história(s) · {formatarData(b.criado_em, false)}
              </p>
              {b.erro && <p className="erro pequeno">{b.erro}</p>}
            </Link>
          ))}
        </div>
      )}
    </>
  );
}
