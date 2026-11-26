export type StatusPlano = 'rascunho' | 'em_execucao' | 'concluido';
export type Prioridade = 'baixa' | 'media' | 'alta' | 'critica';
export type Resultado = 'passou' | 'falhou' | 'bloqueado';
export type ResultadoAtual = Resultado | 'nao_executado';

export interface Resumo {
  total: number;
  passou: number;
  falhou: number;
  bloqueado: number;
  nao_executado: number;
  percentual_executado: number;
  percentual_sucesso: number;
}

export interface Plano {
  id: number;
  nome: string;
  descricao: string | null;
  projeto: string | null;
  versao: string | null;
  responsavel: string | null;
  status: StatusPlano;
  status_rotulo: string;
  criado_em: string | null;
  atualizado_em: string | null;
  resumo?: Resumo;
}

export interface Caso {
  id: number;
  plano_id: number;
  titulo: string;
  pre_condicoes: string | null;
  passos: string;
  resultado_esperado: string;
  prioridade: Prioridade;
  prioridade_rotulo: string;
  ordem: number;
  resultado_atual: ResultadoAtual;
}

export interface PlanoDetalhe extends Plano {
  resumo: Resumo;
  casos: Caso[];
}

export interface Execucao {
  id: number;
  caso_id: number;
  resultado: Resultado;
  resultado_rotulo: string;
  observacao: string | null;
  executado_por: string | null;
  executado_em: string;
}

export type PlanoInput = Pick<Plano, 'nome' | 'descricao' | 'projeto' | 'versao' | 'responsavel'>;
export type CasoInput = Pick<Caso, 'titulo' | 'pre_condicoes' | 'passos' | 'resultado_esperado' | 'prioridade'>;
export interface ExecucaoInput {
  resultado: Resultado;
  observacao: string | null;
  executado_por: string | null;
}

export const ROTULO_RESULTADO: Record<ResultadoAtual, string> = {
  passou: 'Passou',
  falhou: 'Falhou',
  bloqueado: 'Bloqueado',
  nao_executado: 'Não executado',
};

export const ROTULO_PRIORIDADE: Record<Prioridade, string> = {
  baixa: 'Baixa',
  media: 'Média',
  alta: 'Alta',
  critica: 'Crítica',
};
