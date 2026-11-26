import { Link } from 'react-router-dom';
import type { ResultadoImportacao } from '../types';

export const ResultadoImportacaoAviso = ({ r }: { r: ResultadoImportacao }) => (
  <div className="alerta sucesso">
    {r.criados} caso(s) criado(s){r.ignorados > 0 && `, ${r.ignorados} já existia(m) no plano e foi(ram) ignorado(s)`}.{' '}
    <Link to={`/planos/${r.plano_id}`}>Abrir plano de teste →</Link>
  </div>
);
