import DOMPurify from 'dompurify';
import { marked } from 'marked';
import type { TipoMarcador } from '../types';

export const ROTULO_MARCADOR: Record<TipoMarcador, string> = {
  caso: 'Caso de teste',
  regra: 'Regra',
  criterio: 'Critério de aceite',
  excecao: 'Exceção',
  duvida: 'Dúvida',
};

const escapar = (s: string) => s.replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]!);

/** Converte os blocos ::: tipo [prioridade] Título ... ::: em caixas coloridas e renderiza o markdown. */
export function renderizarRegra(conteudo: string): string {
  const linhas = conteudo.split('\n');
  const saida: string[] = [];
  let aberto = false;

  for (const linha of linhas) {
    const m = linha.match(/^\s*:::\s*(caso|regra|criterio|excecao|duvida)\b\s*(?:\[([^\]]+)\])?\s*(.*)$/);
    if (m) {
      if (aberto) saida.push('\n</div>\n');
      const [, tipo, prio, titulo] = m;
      saida.push(
        `\n<div class="marcador marcador-${tipo}"><div class="marcador-tag">${ROTULO_MARCADOR[tipo as TipoMarcador]}${prio ? ` · ${escapar(prio)}` : ''}</div>` +
          (titulo ? `<div class="marcador-titulo">${escapar(titulo)}</div>` : '') +
          '\n',
      );
      aberto = true;
    } else if (aberto && /^\s*:::\s*$/.test(linha)) {
      saida.push('\n</div>\n');
      aberto = false;
    } else {
      saida.push(linha);
    }
  }
  if (aberto) saida.push('\n</div>\n');

  return DOMPurify.sanitize(marked.parse(saida.join('\n'), { async: false, gfm: true, breaks: true }) as string);
}
