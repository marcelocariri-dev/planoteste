export type StatusPlano = 'rascunho' | 'em_execucao' | 'concluido';
export type Prioridade = 'baixa' | 'media' | 'alta' | 'critica';
export type Resultado = 'passou' | 'falhou' | 'bloqueado';
export type ResultadoAtual = Resultado | 'nao_executado';
export type OrigemCaso = 'manual' | 'regra' | 'mcp' | 'backlog';

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
  origem: OrigemCaso;
  origem_rotulo: string;
  referencia: string | null;
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

// ---------------- Módulos
export interface Modulo {
  id: number;
  nome: string;
  tag: string;
  descricao: string | null;
  cor: string;
  total_regras?: number;
  total_documentos?: number;
}
export type ModuloInput = Pick<Modulo, 'nome' | 'tag' | 'descricao' | 'cor'>;

// ---------------- Regras de negócio
export type TipoRegra = 'melhoria' | 'novo_modulo';
export type StatusRegra = 'rascunho' | 'em_validacao' | 'aprovada' | 'reprovada';
export type TipoMarcador = 'caso' | 'regra' | 'criterio' | 'excecao' | 'duvida';
export type Decisao = 'pendente' | 'aprovado' | 'reprovado';

export interface Validacao {
  papel: 'pm' | 'po';
  decisao: Decisao;
  decisao_rotulo: string;
  responsavel: string | null;
  parecer: string | null;
  em: string | null;
}

export interface Marcador {
  tipo: TipoMarcador;
  tipo_rotulo: string;
  titulo: string;
  conteudo: string;
  linha: number;
  prioridade: Prioridade | null;
}

export interface CasoCandidato {
  titulo: string;
  pre_condicoes: string | null;
  passos: string;
  resultado_esperado: string;
  prioridade: Prioridade;
  id_externo: string | null;
}

export type ContagemMarcadores = Record<TipoMarcador, number>;

export interface RegraResumo {
  id: number;
  modulo_id: number;
  modulo: Modulo | null;
  titulo: string;
  tipo: TipoRegra;
  tipo_rotulo: string;
  status: StatusRegra;
  status_rotulo: string;
  versao: number;
  autor: string | null;
  validacoes: Validacao[];
  atualizado_em: string | null;
  contagem_marcadores: ContagemMarcadores;
}

export interface Regra extends RegraResumo {
  conteudo: string;
  marcadores: Marcador[];
  casos_extraidos: CasoCandidato[];
  total_documentos: number;
}

export interface AnaliseRegra {
  marcadores: Marcador[];
  contagem_marcadores: ContagemMarcadores;
  casos_extraidos: CasoCandidato[];
}

export interface VersaoRegra {
  versao: number;
  titulo: string;
  conteudo: string;
  autor: string | null;
  criado_em: string;
}

export interface ResultadoImportacao {
  plano_id: number;
  criados: number;
  ignorados: number;
}

// ---------------- Cofre
export interface Categoria {
  id: number;
  nome: string;
  descricao: string | null;
  total_documentos?: number;
}

export interface Documento {
  id: number;
  titulo: string;
  descricao: string | null;
  categoria_id: number;
  modulo_id: number | null;
  regra_id: number | null;
  origem: 'avulso' | 'validacao';
  origem_rotulo: string;
  arquivo: { nome_original: string; mime_type: string; tamanho: number; extensao: string };
  tags: string[];
  enviado_por: string | null;
  criado_em: string | null;
}

// ---------------- Backlog
export interface PadraoBacklog {
  id: number;
  nome: string;
  descricao: string | null;
  instrucoes: string;
}

export interface Historia {
  codigo: string;
  titulo: string;
  como_um: string;
  quero: string;
  para: string;
  criterios_aceite: string[];
  prioridade: string;
  estimativa: number | null;
  observacoes: string | null;
}

export interface Epico {
  codigo: string;
  titulo: string;
  descricao: string | null;
  historias: Historia[];
}

export type StatusBacklog = 'aguardando_ia' | 'gerado' | 'erro';

export interface BacklogResumo {
  id: number;
  titulo: string;
  modulo_id: number | null;
  padrao_id: number | null;
  status: StatusBacklog;
  status_rotulo: string;
  total_epicos: number;
  total_historias: number;
  modelo: string | null;
  erro: string | null;
  criado_em: string | null;
}

export interface Backlog extends BacklogResumo {
  orientacoes: string;
  documento_ids: number[];
  regra_ids: number[];
  prompt: string;
  resposta_bruta: string | null;
  epicos: Epico[];
}

// ---------------- MCP
export interface ConexaoMcp {
  id: number;
  nome: string;
  url: string;
  possui_token: boolean;
  cabecalhos: Record<string, string> | [];
  ferramenta_casos: string | null;
  argumentos_padrao: Record<string, unknown>;
  ativo: boolean;
  ultimo_teste_em: string | null;
  ultimo_teste_ok: boolean | null;
  ultimo_teste_mensagem: string | null;
}

export interface EsquemaPropriedade {
  type?: string;
  description?: string;
  enum?: string[];
}

export interface FerramentaMcp {
  nome: string;
  descricao: string | null;
  esquema_entrada: { properties?: Record<string, EsquemaPropriedade>; required?: string[] };
}

export interface TesteMcp {
  conexao: ConexaoMcp;
  servidor: { nome: string | null; versao: string | null };
  ferramentas: FerramentaMcp[];
}

export interface BuscaCasosMcp {
  ferramenta: string;
  total: number;
  casos: CasoCandidato[];
  texto_bruto: string;
}
