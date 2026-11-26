import { ROTULO_PRIORIDADE, ROTULO_RESULTADO, type Prioridade, type ResultadoAtual, type StatusPlano } from '../types';

const ROTULO_STATUS: Record<StatusPlano, string> = {
  rascunho: 'Rascunho',
  em_execucao: 'Em execução',
  concluido: 'Concluído',
};

export const StatusBadge = ({ status }: { status: StatusPlano }) => (
  <span className={`badge status-${status}`}>{ROTULO_STATUS[status]}</span>
);

export const ResultadoBadge = ({ resultado }: { resultado: ResultadoAtual }) => (
  <span className={`badge resultado-${resultado}`}>{ROTULO_RESULTADO[resultado]}</span>
);

export const PrioridadeBadge = ({ prioridade }: { prioridade: Prioridade }) => (
  <span className={`badge prioridade-${prioridade}`}>{ROTULO_PRIORIDADE[prioridade]}</span>
);
