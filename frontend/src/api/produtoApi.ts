import { request, upload } from './client';
import type {
  AnaliseRegra, Backlog, BacklogResumo, BuscaCasosMcp, CasoCandidato, Categoria, ConexaoMcp, Documento, Modulo,
  ModuloInput, PadraoBacklog, Regra, RegraResumo, ResultadoImportacao, TesteMcp, VersaoRegra,
} from '../types';

const qs = (p: Record<string, string | number | boolean | null | undefined>) => {
  const s = new URLSearchParams();
  Object.entries(p).forEach(([k, v]) => v !== undefined && v !== null && v !== '' && v !== false && s.set(k, String(v)));
  const t = s.toString();
  return t ? `?${t}` : '';
};

export const moduloApi = {
  listar: () => request<Modulo[]>('GET', '/modulos'),
  criar: (d: ModuloInput) => request<Modulo>('POST', '/modulos', d),
  atualizar: (id: number, d: ModuloInput) => request<Modulo>('PUT', `/modulos/${id}`, d),
  excluir: (id: number) => request<void>('DELETE', `/modulos/${id}`),
};

export interface RegraInput {
  modulo_id: number;
  titulo: string;
  tipo: string;
  conteudo?: string;
  autor?: string | null;
}

export const regraApi = {
  listar: (f: { modulo_id?: number | ''; status?: string; tipo?: string; busca?: string } = {}) =>
    request<RegraResumo[]>('GET', `/regras${qs(f)}`),
  obter: (id: number) => request<Regra>('GET', `/regras/${id}`),
  criar: (d: RegraInput) => request<Regra>('POST', '/regras', d),
  atualizar: (id: number, d: RegraInput) => request<Regra>('PUT', `/regras/${id}`, d),
  excluir: (id: number) => request<void>('DELETE', `/regras/${id}`),
  analisar: (conteudo: string) => request<AnaliseRegra>('POST', '/regras/analisar', { conteudo }),
  fluxo: (id: number, acao: 'enviar' | 'retornar' | 'reabrir') => request<Regra>('POST', `/regras/${id}/fluxo/${acao}`),
  parecer: (id: number, d: { papel: string; decisao: string; responsavel: string; parecer: string | null }) =>
    request<Regra>('POST', `/regras/${id}/parecer`, d),
  versoes: (id: number) => request<VersaoRegra[]>('GET', `/regras/${id}/versoes`),
  gerarPlano: (id: number, planoId: number | null, indices: number[] | null) =>
    request<ResultadoImportacao>('POST', `/regras/${id}/gerar-plano`, { plano_id: planoId, ...(indices ? { indices } : {}) }),
};

export const cofreApi = {
  categorias: () => request<Categoria[]>('GET', '/cofre/categorias'),
  salvarCategoria: (id: number | null, d: { nome: string; descricao?: string | null }) =>
    request<Categoria>(id ? 'PUT' : 'POST', id ? `/cofre/categorias/${id}` : '/cofre/categorias', d),
  excluirCategoria: (id: number) => request<void>('DELETE', `/cofre/categorias/${id}`),
  listar: (f: { busca?: string; categoria_id?: number | ''; modulo_id?: number | ''; regra_id?: number; sem_modulo?: boolean } = {}) =>
    request<Documento[]>('GET', `/cofre/documentos${qs(f)}`),
  enviar: (arquivo: File, d: Record<string, string | number | null | undefined>) => {
    const form = new FormData();
    form.append('arquivo', arquivo);
    Object.entries(d).forEach(([k, v]) => v !== null && v !== undefined && v !== '' && form.append(k, String(v)));
    return upload<Documento>('/cofre/documentos', form);
  },
  atualizar: (id: number, d: { titulo: string; descricao: string | null; categoria_id: number; modulo_id: number | null; tags: string[] }) =>
    request<Documento>('PUT', `/cofre/documentos/${id}`, d),
  excluir: (id: number) => request<void>('DELETE', `/cofre/documentos/${id}`),
  urlDownload: (id: number, inline = false) => `/api/cofre/documentos/${id}/download${inline ? '?inline=1' : ''}`,
};

export const backlogApi = {
  configuracao: () => request<{ ia_disponivel: boolean; variaveis: string[] }>('GET', '/backlog/configuracao'),
  padroes: () => request<PadraoBacklog[]>('GET', '/backlog/padroes'),
  salvarPadrao: (id: number | null, d: Omit<PadraoBacklog, 'id'>) =>
    request<PadraoBacklog>(id ? 'PUT' : 'POST', id ? `/backlog/padroes/${id}` : '/backlog/padroes', d),
  excluirPadrao: (id: number) => request<void>('DELETE', `/backlog/padroes/${id}`),
  listar: () => request<BacklogResumo[]>('GET', '/backlogs'),
  obter: (id: number) => request<Backlog>('GET', `/backlogs/${id}`),
  gerar: (d: { titulo: string; padrao_id: number; modulo_id: number | null; orientacoes: string; documento_ids: number[]; regra_ids: number[] }) =>
    request<Backlog>('POST', '/backlogs', d),
  importarResposta: (id: number, resposta: string) => request<Backlog>('POST', `/backlogs/${id}/resposta`, { resposta }),
  excluir: (id: number) => request<void>('DELETE', `/backlogs/${id}`),
  gerarPlano: (id: number, planoId: number | null, historias: string[] | null) =>
    request<ResultadoImportacao>('POST', `/backlogs/${id}/gerar-plano`, { plano_id: planoId, ...(historias ? { historias } : {}) }),
};

export interface ConexaoMcpInput {
  nome: string;
  url: string;
  token?: string;
  cabecalhos?: string;
  ferramenta_casos: string | null;
  argumentos_padrao: string;
  ativo: boolean;
}

export const mcpApi = {
  listar: () => request<ConexaoMcp[]>('GET', '/mcp/conexoes'),
  salvar: (id: number | null, d: ConexaoMcpInput) =>
    request<ConexaoMcp>(id ? 'PUT' : 'POST', id ? `/mcp/conexoes/${id}` : '/mcp/conexoes', d),
  excluir: (id: number) => request<void>('DELETE', `/mcp/conexoes/${id}`),
  testar: (id: number) => request<TesteMcp>('POST', `/mcp/conexoes/${id}/testar`),
  buscarCasos: (id: number, ferramenta: string | null, argumentos: Record<string, unknown> | null) =>
    request<BuscaCasosMcp>('POST', `/mcp/conexoes/${id}/casos`, { ferramenta, ...(argumentos ? { argumentos } : {}) }),
  importar: (id: number, casos: CasoCandidato[], planoId: number | null, novoPlano: { nome: string } | null) =>
    request<ResultadoImportacao>('POST', `/mcp/conexoes/${id}/importar`, { casos, plano_id: planoId, novo_plano: novoPlano }),
};
