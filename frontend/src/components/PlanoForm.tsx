import { useState, type FormEvent } from 'react';
import { planoTesteApi } from '../api/planoTesteApi';
import type { Plano, PlanoInput } from '../types';
import { Campo } from './Campo';
import { useFormulario } from './useFormulario';

interface Props {
  plano?: Plano;
  onSalvo: (plano: Plano) => void;
  onCancelar: () => void;
}

export function PlanoForm({ plano, onSalvo, onCancelar }: Props) {
  const [dados, setDados] = useState<PlanoInput>({
    nome: plano?.nome ?? '',
    descricao: plano?.descricao ?? '',
    projeto: plano?.projeto ?? '',
    versao: plano?.versao ?? '',
    responsavel: plano?.responsavel ?? '',
  });
  const { salvando, erros, mensagem, enviar } = useFormulario();
  const set = (campo: keyof PlanoInput) => (e: { target: { value: string } }) =>
    setDados({ ...dados, [campo]: e.target.value });

  const submeter = (e: FormEvent) => {
    e.preventDefault();
    enviar(async () => {
      const salvo = plano
        ? await planoTesteApi.atualizarPlano(plano.id, dados)
        : await planoTesteApi.criarPlano(dados);
      onSalvo(salvo);
    });
  };

  return (
    <form onSubmit={submeter} className="form">
      {mensagem && <div className="alerta erro">{mensagem}</div>}
      <Campo rotulo="Nome" obrigatorio erro={erros.nome}>
        <input value={dados.nome} onChange={set('nome')} maxLength={150} autoFocus />
      </Campo>
      <div className="linha">
        <Campo rotulo="Projeto / módulo" erro={erros.projeto}>
          <input value={dados.projeto ?? ''} onChange={set('projeto')} placeholder="Ex.: SoftcomShop - PDV" />
        </Campo>
        <Campo rotulo="Versão" erro={erros.versao}>
          <input value={dados.versao ?? ''} onChange={set('versao')} placeholder="Ex.: 2.4.0" />
        </Campo>
      </div>
      <Campo rotulo="Responsável" erro={erros.responsavel}>
        <input value={dados.responsavel ?? ''} onChange={set('responsavel')} />
      </Campo>
      <Campo rotulo="Descrição / escopo" erro={erros.descricao}>
        <textarea rows={3} value={dados.descricao ?? ''} onChange={set('descricao')} />
      </Campo>
      <footer className="form-acoes">
        <button type="button" className="btn" onClick={onCancelar}>Cancelar</button>
        <button type="submit" className="btn btn-primario" disabled={salvando}>
          {salvando ? 'Salvando…' : 'Salvar'}
        </button>
      </footer>
    </form>
  );
}
