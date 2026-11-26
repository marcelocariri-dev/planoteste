import { useEffect, useState, type FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { moduloApi, regraApi } from '../api/produtoApi';
import { Campo } from '../components/Campo';
import { Modal } from '../components/Modal';
import { ModuloBadge } from '../components/ModuloBadge';
import { ContagemTags, DecisaoBadge, StatusRegraBadge } from '../components/RegraBadges';
import { useFormulario } from '../components/useFormulario';
import { formatarData } from '../lib/util';
import type { Modulo, RegraResumo } from '../types';

function NovaRegraForm({ modulos, onCancelar }: { modulos: Modulo[]; onCancelar: () => void }) {
  const navigate = useNavigate();
  const [d, setD] = useState({ modulo_id: modulos[0]?.id ?? 0, titulo: '', tipo: 'melhoria', autor: '' });
  const { salvando, erros, mensagem, enviar } = useFormulario();

  const submeter = (e: FormEvent) => {
    e.preventDefault();
    enviar(async () => {
      const r = await regraApi.criar({ ...d, conteudo: '' });
      navigate(`/regras/${r.id}`);
    });
  };

  if (modulos.length === 0) {
    return <div className="alerta">Cadastre um módulo antes. <Link to="/modulos">Ir para módulos →</Link></div>;
  }

  return (
    <form className="form" onSubmit={submeter}>
      {mensagem && <div className="alerta erro">{mensagem}</div>}
      <Campo rotulo="Título" obrigatorio erro={erros.titulo}>
        <input value={d.titulo} onChange={(e) => setD({ ...d, titulo: e.target.value })} autoFocus placeholder="Ex.: Limite de desconto por perfil" />
      </Campo>
      <div className="linha">
        <Campo rotulo="Módulo" obrigatorio erro={erros.modulo_id}>
          <select value={d.modulo_id} onChange={(e) => setD({ ...d, modulo_id: Number(e.target.value) })}>
            {modulos.map((m) => <option key={m.id} value={m.id}>{m.tag} · {m.nome}</option>)}
          </select>
        </Campo>
        <Campo rotulo="Tipo" obrigatorio>
          <select value={d.tipo} onChange={(e) => setD({ ...d, tipo: e.target.value })}>
            <option value="melhoria">Melhoria</option>
            <option value="novo_modulo">Novo módulo</option>
          </select>
        </Campo>
      </div>
      <Campo rotulo="Autor (PM/PO)">
        <input value={d.autor} onChange={(e) => setD({ ...d, autor: e.target.value })} />
      </Campo>
      <footer className="form-acoes">
        <button type="button" className="btn" onClick={onCancelar}>Cancelar</button>
        <button className="btn btn-primario" disabled={salvando}>Criar e escrever a regra</button>
      </footer>
    </form>
  );
}

export function RegrasPage() {
  const [regras, setRegras] = useState<RegraResumo[]>([]);
  const [modulos, setModulos] = useState<Modulo[]>([]);
  const [filtro, setFiltro] = useState({ modulo_id: '' as number | '', status: '', tipo: '', busca: '' });
  const [nova, setNova] = useState(false);

  useEffect(() => { moduloApi.listar().then(setModulos); }, []);
  useEffect(() => {
    const t = setTimeout(() => regraApi.listar(filtro).then(setRegras), 250);
    return () => clearTimeout(t);
  }, [filtro]);

  return (
    <>
      <div className="topo-pagina">
        <div>
          <h1>Validação de regras de negócio</h1>
          <p className="muted">Documentos de melhorias e novos módulos, validados por PM e PO.</p>
        </div>
        <button className="btn btn-primario" onClick={() => setNova(true)}>+ Nova regra</button>
      </div>

      <div className="filtros filtros-4">
        <input placeholder="Buscar no título ou no texto…" value={filtro.busca} onChange={(e) => setFiltro({ ...filtro, busca: e.target.value })} />
        <select value={filtro.modulo_id} onChange={(e) => setFiltro({ ...filtro, modulo_id: Number(e.target.value) || '' })}>
          <option value="">Todos os módulos</option>
          {modulos.map((m) => <option key={m.id} value={m.id}>{m.tag}</option>)}
        </select>
        <select value={filtro.status} onChange={(e) => setFiltro({ ...filtro, status: e.target.value })}>
          <option value="">Todos os status</option>
          <option value="rascunho">Rascunho</option>
          <option value="em_validacao">Em validação</option>
          <option value="aprovada">Aprovada</option>
          <option value="reprovada">Reprovada</option>
        </select>
        <select value={filtro.tipo} onChange={(e) => setFiltro({ ...filtro, tipo: e.target.value })}>
          <option value="">Melhoria e novo módulo</option>
          <option value="melhoria">Melhoria</option>
          <option value="novo_modulo">Novo módulo</option>
        </select>
      </div>

      <table className="tabela">
        <thead>
          <tr>
            <th>Regra</th><th style={{ width: 120 }}>Módulo</th><th style={{ width: 120 }}>Status</th>
            <th style={{ width: 120 }}>PM / PO</th><th style={{ width: 170 }}>Tags</th><th style={{ width: 130 }}>Atualizada</th>
          </tr>
        </thead>
        <tbody>
          {regras.map((r) => (
            <tr key={r.id}>
              <td>
                <Link to={`/regras/${r.id}`} className="link">{r.titulo}</Link>
                <div className="muted pequeno">{r.tipo_rotulo} · v{r.versao}{r.autor && ` · ${r.autor}`}</div>
              </td>
              <td><ModuloBadge modulo={r.modulo} /></td>
              <td><StatusRegraBadge status={r.status} /></td>
              <td className="linha-inline">{r.validacoes.map((v) => <DecisaoBadge key={v.papel} papel={v.papel} decisao={v.decisao} />)}</td>
              <td><ContagemTags c={r.contagem_marcadores} /></td>
              <td className="muted pequeno">{formatarData(r.atualizado_em)}</td>
            </tr>
          ))}
          {regras.length === 0 && <tr><td colSpan={6} className="muted centro">Nenhuma regra encontrada.</td></tr>}
        </tbody>
      </table>

      {nova && (
        <Modal titulo="Nova regra de negócio" onFechar={() => setNova(false)}>
          <NovaRegraForm modulos={modulos} onCancelar={() => setNova(false)} />
        </Modal>
      )}
    </>
  );
}
