import { useRef, useState } from 'react';

interface Props {
  onArquivos: (arquivos: File[]) => void;
  desabilitado?: boolean;
  texto?: string;
}

/** Área de arrastar e soltar (ou clicar) para escolher arquivos da máquina. */
export function AreaUpload({ onArquivos, desabilitado, texto }: Props) {
  const input = useRef<HTMLInputElement>(null);
  const [sobre, setSobre] = useState(false);

  return (
    <div
      className={`area-upload ${sobre ? 'sobre' : ''} ${desabilitado ? 'desabilitada' : ''}`}
      onClick={() => !desabilitado && input.current?.click()}
      onDragOver={(e) => { e.preventDefault(); setSobre(true); }}
      onDragLeave={() => setSobre(false)}
      onDrop={(e) => {
        e.preventDefault();
        setSobre(false);
        if (!desabilitado && e.dataTransfer.files.length) onArquivos(Array.from(e.dataTransfer.files));
      }}
    >
      <strong>⇪ {texto ?? 'Arraste arquivos aqui ou clique para escolher'}</strong>
      <small className="muted">PDF, Word, planilhas, imagens, textos… até 25 MB cada</small>
      <input
        ref={input}
        type="file"
        multiple
        hidden
        onChange={(e) => {
          if (e.target.files?.length) onArquivos(Array.from(e.target.files));
          e.target.value = '';
        }}
      />
    </div>
  );
}
