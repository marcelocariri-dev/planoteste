import { useEffect, useState, type FormEvent } from 'react';
import { planoTesteApi } from '../api/planoTesteApi';
import type { Caso, Execucao, Resultado } from '../types';
import { ResultadoBadge } from './Badges';
import { Campo } from './Campo';
import { useFormulario } from './useFormulario';

interface Props {
  caso: Caso;
  podeExecutar: boolean;
  onRegistrado: () => void;
}

const OPCOES: { valor: Resultado; rotulo: string }[] = [
  { valor: 'passou', rotulo: '✓ Passou' },
  { valor: 'falhou', rotulo: '✗ Falhou' },
  { valor: 'bloqueado', rotulo: '⊘ Bloqueado' },
];

const NOME_EXECUTOR = 'plano-teste:executor';

export function ExecucaoPainel({ caso, podeExecutar, onRegistrado }: Props) {
  const [historico, setHistorico] = useState<Execucao[]>([]);
  const [resultado, setResultado] = useState<Resultado>('passou');
  const [observacao, setObservacao] = useState('');
  const [executadoPor, setExecutadoPor] = useState(() => {
    try { return localStorage.getItem(NOME_EXECUTOR) ?? ''; } catch { return ''; }
  });
  const { salvando, erros, mensagem, enviar } = useFormulario();

  const carregar = () => planoTesteApi.listarExecucoes(caso.id).then(setHistorico);
  useEffect(() => { carregar(); }, [caso.id]); // eslint-disable-line react-hooks/exhaustive-deps

  const submeter = (e: FormEvent) => {
    e.preventDefault();
    enviar(async () => {
      await planoTesteApi.registrarExecucao(caso.id, {
        resultado,
        observacao: observacao || null,
        executado_por: executadoPor || null,
      });
      try { localStorage.setItem(NOME_EXECUTOR, executadoPor); } catch { /* ignore */ }
      setObservacao('');
      await carregar();
      onRegistrado();
    });
  };

  return (
    <div className="execucao">
      <section className="caso-detalhe">
        {caso.pre_condicoes && (<><h4>Pré-condições</h4><p className="pre">{caso.pre_condicoes}</p></>)}
        <h4>Passos</h4>
        <p className="pre">{caso.passos}</p>
        <h4>Resultado esperado</h4>
        <p className="pre">{caso.resultado_esperado}</p>
      </section>

      {podeExecutar ? (
        <form onSubmit={submeter} className="form">
          {mensagem && <div className="alerta erro">{mensagem}</div>}
          <div className="opcoes-resultado">
            {OPCOES.map((o) => (
              <button
                type="button"
                key={o.valor}
                className={`btn opcao-${o.valor} ${resultado === o.valor ? 'ativo' : ''}`}
                onClick={() => setResultado(o.valor)}
              >
                {o.rotulo}
              </button>
            ))}
          </div>
          <Campo rotulo="Observação / resultado obtido" obrigatorio={resultado !== 'passou'} erro={erros.observacao}>
            <textarea rows={3} value={observacao} onChange={(e) => setObservacao(e.target.value)} />
          </Campo>
          <Campo rotulo="Executado por" erro={erros.executado_por}>
            <input value={executadoPor} onChange={(e) => setExecutadoPor(e.target.value)} />
          </Campo>
          <footer className="form-acoes">
            <button type="submit" className="btn btn-primario" disabled={salvando}>
              {salvando ? 'Registrando…' : 'Registrar execução'}
            </button>
          </footer>
        </form>
      ) : (
        <div className="alerta">Plano concluído. Reabra o plano para registrar novas execuções.</div>
      )}

      <h4>Histórico</h4>
      {historico.length === 0 ? (
        <p className="muted">Nenhuma execução registrada.</p>
      ) : (
        <ul className="historico">
          {historico.map((h) => (
            <li key={h.id}>
              <ResultadoBadge resultado={h.resultado} />
              <span className="muted">
                {new Date(h.executado_em).toLocaleString('pt-BR')}
                {h.executado_por && ` · ${h.executado_por}`}
              </span>
              {h.observacao && <p className="pre">{h.observacao}</p>}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
