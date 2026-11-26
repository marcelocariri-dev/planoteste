import { useEffect, useState } from 'react';
import { planoTesteApi } from '../api/planoTesteApi';
import type { Plano } from '../types';

export type Destino = { tipo: 'novo'; nome: string } | { tipo: 'existente'; planoId: number | null };

interface Props {
  valor: Destino;
  onChange: (d: Destino) => void;
  nomeSugerido?: string;
  permitirNovo?: boolean;
}

/** Escolhe se os casos vão para um plano novo ou para um plano existente (não concluído). */
export function DestinoPlano({ valor, onChange, nomeSugerido = '', permitirNovo = true }: Props) {
  const [planos, setPlanos] = useState<Plano[]>([]);

  useEffect(() => {
    planoTesteApi.listarPlanos().then((p) => setPlanos(p.filter((x) => x.status !== 'concluido')));
  }, []);

  return (
    <div className="destino-plano">
      {permitirNovo && (
        <label className="radio">
          <input type="radio" checked={valor.tipo === 'novo'} onChange={() => onChange({ tipo: 'novo', nome: nomeSugerido })} />
          Novo plano
        </label>
      )}
      {valor.tipo === 'novo' && (
        <input value={valor.nome} placeholder="Nome do novo plano" onChange={(e) => onChange({ tipo: 'novo', nome: e.target.value })} />
      )}
      <label className="radio">
        <input
          type="radio"
          checked={valor.tipo === 'existente'}
          onChange={() => onChange({ tipo: 'existente', planoId: planos[0]?.id ?? null })}
        />
        Plano existente
      </label>
      {valor.tipo === 'existente' && (
        <select value={valor.planoId ?? ''} onChange={(e) => onChange({ tipo: 'existente', planoId: Number(e.target.value) || null })}>
          {planos.length === 0 && <option value="">Nenhum plano aberto</option>}
          {planos.map((p) => (
            <option key={p.id} value={p.id}>
              #{p.id} · {p.nome} {p.versao ? `(v${p.versao})` : ''}
            </option>
          ))}
        </select>
      )}
    </div>
  );
}
