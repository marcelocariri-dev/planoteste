import type { Modulo } from '../types';

export const ModuloBadge = ({ modulo }: { modulo: Pick<Modulo, 'tag' | 'cor' | 'nome'> | null | undefined }) =>
  modulo ? (
    <span className="badge-modulo" style={{ borderColor: modulo.cor, color: modulo.cor }} title={modulo.nome}>
      <i style={{ background: modulo.cor }} />
      {modulo.tag}
    </span>
  ) : (
    <span className="badge-modulo muted">SEM MÓDULO</span>
  );
