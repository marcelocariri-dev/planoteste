import { request } from './client';
import type { Caso, CasoInput, Execucao, ExecucaoInput, Plano, PlanoDetalhe, PlanoInput } from '../types';

export const planoTesteApi = {
  listarPlanos: (busca = '', status = '') => {
    const params = new URLSearchParams();
    if (busca) params.set('busca', busca);
    if (status) params.set('status', status);
    const qs = params.toString();
    return request<Plano[]>('GET', `/planos${qs ? `?${qs}` : ''}`);
  },
  obterPlano: (id: number) => request<PlanoDetalhe>('GET', `/planos/${id}`),
  criarPlano: (dados: PlanoInput) => request<Plano>('POST', '/planos', dados),
  atualizarPlano: (id: number, dados: PlanoInput) => request<Plano>('PUT', `/planos/${id}`, dados),
  excluirPlano: (id: number) => request<void>('DELETE', `/planos/${id}`),
  concluirPlano: (id: number) => request<Plano>('POST', `/planos/${id}/concluir`),
  reabrirPlano: (id: number) => request<Plano>('POST', `/planos/${id}/reabrir`),

  adicionarCaso: (planoId: number, dados: CasoInput) => request<Caso>('POST', `/planos/${planoId}/casos`, dados),
  atualizarCaso: (id: number, dados: CasoInput) => request<Caso>('PUT', `/casos/${id}`, dados),
  removerCaso: (id: number) => request<void>('DELETE', `/casos/${id}`),

  listarExecucoes: (casoId: number) => request<Execucao[]>('GET', `/casos/${casoId}/execucoes`),
  registrarExecucao: (casoId: number, dados: ExecucaoInput) =>
    request<Execucao>('POST', `/casos/${casoId}/execucoes`, dados),
};
