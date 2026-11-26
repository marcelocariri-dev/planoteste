import { useState, type FormEvent } from 'react';
import { planoTesteApi } from '../api/planoTesteApi';
import { ROTULO_PRIORIDADE, type Caso, type CasoInput, type Prioridade } from '../types';
import { Campo } from './Campo';
import { useFormulario } from './useFormulario';

interface Props {
  planoId: number;
  caso?: Caso;
  onSalvo: () => void;
  onCancelar: () => void;
}

export function CasoForm({ planoId, caso, onSalvo, onCancelar }: Props) {
  const [dados, setDados] = useState<CasoInput>({
    titulo: caso?.titulo ?? '',
    pre_condicoes: caso?.pre_condicoes ?? '',
    passos: caso?.passos ?? '',
    resultado_esperado: caso?.resultado_esperado ?? '',
    prioridade: caso?.prioridade ?? 'media',
  });
  const { salvando, erros, mensagem, enviar } = useFormulario();
  const set = (campo: keyof CasoInput) => (e: { target: { value: string } }) =>
    setDados({ ...dados, [campo]: e.target.value });

  const submeter = (e: FormEvent) => {
    e.preventDefault();
    enviar(async () => {
      if (caso) await planoTesteApi.atualizarCaso(caso.id, dados);
      else await planoTesteApi.adicionarCaso(planoId, dados);
      onSalvo();
    });
  };

  return (
    <form onSubmit={submeter} className="form">
      {mensagem && <div className="alerta erro">{mensagem}</div>}
      <div className="linha">
        <Campo rotulo="Título" obrigatorio erro={erros.titulo}>
          <input value={dados.titulo} onChange={set('titulo')} maxLength={200} autoFocus />
        </Campo>
        <Campo rotulo="Prioridade" obrigatorio erro={erros.prioridade}>
          <select value={dados.prioridade} onChange={set('prioridade')}>
            {(Object.keys(ROTULO_PRIORIDADE) as Prioridade[]).map((p) => (
              <option key={p} value={p}>{ROTULO_PRIORIDADE[p]}</option>
            ))}
          </select>
        </Campo>
      </div>
      <Campo rotulo="Pré-condições" erro={erros.pre_condicoes}>
        <textarea rows={2} value={dados.pre_condicoes ?? ''} onChange={set('pre_condicoes')} />
      </Campo>
      <Campo rotulo="Passos" obrigatorio erro={erros.passos}>
        <textarea rows={5} value={dados.passos} onChange={set('passos')} placeholder={'1. ...\n2. ...'} />
      </Campo>
      <Campo rotulo="Resultado esperado" obrigatorio erro={erros.resultado_esperado}>
        <textarea rows={2} value={dados.resultado_esperado} onChange={set('resultado_esperado')} />
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
