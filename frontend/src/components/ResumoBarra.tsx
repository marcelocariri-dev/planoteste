import type { Resumo } from '../types';

export function ResumoBarra({ resumo, compacto }: { resumo: Resumo; compacto?: boolean }) {
  const pct = (n: number) => (resumo.total ? (n / resumo.total) * 100 : 0);

  return (
    <div className="resumo">
      <div className="barra" title={`${resumo.percentual_executado}% executado`}>
        <span className="seg passou" style={{ width: `${pct(resumo.passou)}%` }} />
        <span className="seg falhou" style={{ width: `${pct(resumo.falhou)}%` }} />
        <span className="seg bloqueado" style={{ width: `${pct(resumo.bloqueado)}%` }} />
      </div>
      {compacto ? (
        <small className="muted">
          {resumo.total - resumo.nao_executado}/{resumo.total} executados · {resumo.percentual_sucesso}% sucesso
        </small>
      ) : (
        <div className="resumo-numeros">
          <div><strong>{resumo.total}</strong><span>Casos</span></div>
          <div className="txt-passou"><strong>{resumo.passou}</strong><span>Passou</span></div>
          <div className="txt-falhou"><strong>{resumo.falhou}</strong><span>Falhou</span></div>
          <div className="txt-bloqueado"><strong>{resumo.bloqueado}</strong><span>Bloqueado</span></div>
          <div><strong>{resumo.nao_executado}</strong><span>Não executado</span></div>
          <div><strong>{resumo.percentual_executado}%</strong><span>Executado</span></div>
          <div><strong>{resumo.percentual_sucesso}%</strong><span>Sucesso</span></div>
        </div>
      )}
    </div>
  );
}
