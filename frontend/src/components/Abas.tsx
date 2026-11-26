import type { ReactNode } from 'react';

export interface Aba<T extends string> {
  id: T;
  rotulo: ReactNode;
}

export function Abas<T extends string>({ abas, ativa, onTrocar }: { abas: Aba<T>[]; ativa: T; onTrocar: (id: T) => void }) {
  return (
    <nav className="abas" role="tablist">
      {abas.map((a) => (
        <button key={a.id} role="tab" aria-selected={a.id === ativa} className={a.id === ativa ? 'ativa' : ''} onClick={() => onTrocar(a.id)}>
          {a.rotulo}
        </button>
      ))}
    </nav>
  );
}
