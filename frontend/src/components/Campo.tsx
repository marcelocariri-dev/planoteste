import type { ReactNode } from 'react';

interface Props {
  rotulo: string;
  erro?: string[];
  obrigatorio?: boolean;
  children: ReactNode;
}

export function Campo({ rotulo, erro, obrigatorio, children }: Props) {
  return (
    <label className="campo">
      <span>
        {rotulo}
        {obrigatorio && <em> *</em>}
      </span>
      {children}
      {erro?.map((e) => <small key={e} className="erro">{e}</small>)}
    </label>
  );
}
