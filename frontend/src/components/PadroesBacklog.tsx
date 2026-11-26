import { useState } from 'react';
import { mensagemErro } from '../api/client';
import { backlogApi } from '../api/produtoApi';
import type { PadraoBacklog } from '../types';
import { Campo } from './Campo';

/** Lista e edita os padrões (templates de prompt) do gerador de backlog. */
export function PadroesBacklog({ padroes, onAlterado }: { padroes: PadraoBacklog[]; onAlterado: () => void }) {
  const [sel, setSel] = useState<PadraoBacklog | null>(padroes[0] ?? null);
  const [d, setD] = useState<Omit<PadraoBacklog, 'id'>>(padroes[0] ?? { nome: '', descricao: '', instrucoes: '' });
  const [erro, setErro] = useState('');
  const [ok, setOk] = useState('');

  const escolher = (p: PadraoBacklog | null) => {
    setSel(p);
    setD(p ?? { nome: '', descricao: '', instrucoes: 'Gere o backlog do módulo {{modulo}}.\n\n## Orientações\n{{orientacoes}}\n\n## Regras\n{{regras}}\n\n## Documentos\n{{documentos}}\n' });
    setErro('');
    setOk('');
  };

  const salvar = async () => {
    try {
      const salvo = await backlogApi.salvarPadrao(sel?.id ?? null, d);
      setSel(salvo);
      setOk('Padrão salvo.');
      setErro('');
      onAlterado();
    } catch (e) {
      setErro(mensagemErro(e));
    }
  };

  return (
    <div className="grade-1-3">
      <ul className="lista-versoes">
        {padroes.map((p) => (
          <li key={p.id} className={sel?.id === p.id ? 'ativa' : ''} onClick={() => escolher(p)}>{p.nome}</li>
        ))}
        <li className={!sel ? 'ativa' : ''} onClick={() => escolher(null)}>+ Novo padrão</li>
      </ul>
      <div className="form">
        {erro && <div className="alerta erro">{erro}</div>}
        {ok && <div className="alerta sucesso">{ok}</div>}
        <Campo rotulo="Nome" obrigatorio><input value={d.nome} onChange={(e) => setD({ ...d, nome: e.target.value })} /></Campo>
        <Campo rotulo="Descrição"><input value={d.descricao ?? ''} onChange={(e) => setD({ ...d, descricao: e.target.value })} /></Campo>
        <Campo rotulo="Instruções (prompt)" obrigatorio>
          <textarea rows={16} className="mono" value={d.instrucoes} onChange={(e) => setD({ ...d, instrucoes: e.target.value })} />
        </Campo>
        <p className="muted pequeno">
          Variáveis: <code>{'{{modulo}}'}</code> <code>{'{{orientacoes}}'}</code> (obrigatória) <code>{'{{regras}}'}</code> <code>{'{{documentos}}'}</code>.
          O formato JSON da resposta é acrescentado automaticamente no final.
        </p>
        <footer className="form-acoes">
          {sel && (
            <button className="btn btn-perigo" onClick={() => backlogApi.excluirPadrao(sel.id).then(() => { escolher(null); onAlterado(); }).catch((e) => setErro(mensagemErro(e)))}>
              Excluir
            </button>
          )}
          <button className="btn btn-primario" onClick={salvar}>Salvar padrão</button>
        </footer>
      </div>
    </div>
  );
}
