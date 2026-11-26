import { useEffect, type ReactNode } from 'react';

interface Props {
  titulo: string;
  onFechar: () => void;
  children: ReactNode;
  largo?: boolean;
}

export function Modal({ titulo, onFechar, children, largo }: Props) {
  useEffect(() => {
    const esc = (e: KeyboardEvent) => e.key === 'Escape' && onFechar();
    window.addEventListener('keydown', esc);
    return () => window.removeEventListener('keydown', esc);
  }, [onFechar]);

  return (
    <div className="modal-fundo" onMouseDown={onFechar}>
      <div className={`modal ${largo ? 'modal-largo' : ''}`} onMouseDown={(e) => e.stopPropagation()} role="dialog">
        <header className="modal-cabecalho">
          <h2>{titulo}</h2>
          <button className="btn-icone" onClick={onFechar} aria-label="Fechar">×</button>
        </header>
        <div className="modal-corpo">{children}</div>
      </div>
    </div>
  );
}
