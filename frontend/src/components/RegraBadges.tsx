import type { ContagemMarcadores, Decisao, StatusRegra, TipoMarcador } from '../types';
import { ROTULO_MARCADOR } from '../lib/markdown';

const ROTULO_STATUS: Record<StatusRegra, string> = {
  rascunho: 'Rascunho',
  em_validacao: 'Em validação',
  aprovada: 'Aprovada',
  reprovada: 'Reprovada',
};

export const StatusRegraBadge = ({ status }: { status: StatusRegra }) => (
  <span className={`badge regra-${status}`}>{ROTULO_STATUS[status]}</span>
);

export const DecisaoBadge = ({ papel, decisao }: { papel: string; decisao: Decisao }) => (
  <span className={`badge decisao-${decisao}`} title={`${papel.toUpperCase()}: ${decisao}`}>
    {papel.toUpperCase()} {decisao === 'aprovado' ? '✓' : decisao === 'reprovado' ? '✗' : '…'}
  </span>
);

export const ICONE_MARCADOR: Record<TipoMarcador, string> = { caso: '🧪', regra: '📏', criterio: '✅', excecao: '⚠', duvida: '❓' };

export const ContagemTags = ({ c }: { c: ContagemMarcadores }) => (
  <span className="contagem-tags">
    {(Object.keys(c) as TipoMarcador[]).filter((t) => c[t] > 0).map((t) => (
      <span key={t} className={`chip-tag tag-${t}`} title={ROTULO_MARCADOR[t]}>
        {ICONE_MARCADOR[t]} {c[t]}
      </span>
    ))}
  </span>
);
