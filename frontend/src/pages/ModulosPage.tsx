import { useEffect, useState, type FormEvent } from 'react';
import { mensagemErro } from '../api/client';
import { moduloApi } from '../api/produtoApi';
import { Campo } from '../components/Campo';
import { Modal } from '../components/Modal';
import { ModuloBadge } from '../components/ModuloBadge';
import { useFormulario } from '../components/useFormulario';
import type { Modulo, ModuloInput } from '../types';

const CORES = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0891b2', '#db2777', '#4b5563'];

function ModuloForm({ modulo, onSalvo, onCancelar }: { modulo?: Modulo; onSalvo: () => void; onCancelar: () => void }) {
  const [d, setD] = useState<ModuloInput>({ nome: modulo?.nome ?? '', tag: modulo?.tag ?? '', descricao: modulo?.descricao ?? '', cor: modulo?.cor ?? CORES[0] });
  const { salvando, erros, mensagem, enviar } = useFormulario();

  const submeter = (e: FormEvent) => {
    e.preventDefault();
    enviar(async () => {
      if (modulo) await moduloApi.atualizar(modulo.id, d);
      else await moduloApi.criar(d);
      onSalvo();
    });
  };

  return (
    <form className="form" onSubmit={submeter}>
      {mensagem && <div className="alerta erro">{mensagem}</div>}
      <div className="linha">
        <Campo rotulo="Nome" obrigatorio erro={erros.nome}>
          <input value={d.nome} onChange={(e) => setD({ ...d, nome: e.target.value })} autoFocus placeholder="Ex.: Frente de caixa" />
        </Campo>
        <Campo rotulo="Tag (departamento)" erro={erros.tag}>
          <input value={d.tag} onChange={(e) => setD({ ...d, tag: e.target.value.toUpperCase() })} placeholder="Gerada a partir do nome" />
        </Campo>
      </div>
      <Campo rotulo="Descrição" erro={erros.descricao}>
        <textarea rows={2} value={d.descricao ?? ''} onChange={(e) => setD({ ...d, descricao: e.target.value })} />
      </Campo>
      <Campo rotulo="Cor">
        <div className="cores">
          {CORES.map((c) => (
            <button type="button" key={c} className={`cor ${d.cor === c ? 'ativa' : ''}`} style={{ background: c }} onClick={() => setD({ ...d, cor: c })} aria-label={c} />
          ))}
          <input type="color" value={d.cor} onChange={(e) => setD({ ...d, cor: e.target.value })} />
        </div>
      </Campo>
      <footer className="form-acoes">
        <button type="button" className="btn" onClick={onCancelar}>Cancelar</button>
        <button className="btn btn-primario" disabled={salvando}>{salvando ? 'Salvando…' : 'Salvar'}</button>
      </footer>
    </form>
  );
}

export function ModulosPage() {
  const [modulos, setModulos] = useState<Modulo[]>([]);
  const [editando, setEditando] = useState<Modulo | 'novo' | null>(null);
  const [erro, setErro] = useState('');

  const carregar = () => moduloApi.listar().then(setModulos);
  useEffect(() => { carregar(); }, []);

  return (
    <>
      <div className="topo-pagina">
        <div>
          <h1>Módulos</h1>
          <p className="muted">A tag do módulo departamentaliza as regras de negócio e os documentos do cofre.</p>
        </div>
        <button className="btn btn-primario" onClick={() => setEditando('novo')}>+ Novo módulo</button>
      </div>
      {erro && <div className="alerta erro">{erro}</div>}

      <table className="tabela">
        <thead>
          <tr><th>Tag</th><th>Nome</th><th>Descrição</th><th style={{ width: 90 }}>Regras</th><th style={{ width: 110 }}>Documentos</th><th style={{ width: 160 }} /></tr>
        </thead>
        <tbody>
          {modulos.map((m) => (
            <tr key={m.id}>
              <td><ModuloBadge modulo={m} /></td>
              <td>{m.nome}</td>
              <td className="muted">{m.descricao}</td>
              <td>{m.total_regras}</td>
              <td>{m.total_documentos}</td>
              <td className="acoes-linha">
                <button className="btn btn-pequeno" onClick={() => setEditando(m)}>Editar</button>
                <button
                  className="btn btn-pequeno btn-perigo"
                  onClick={() => window.confirm(`Excluir o módulo ${m.nome}?`) &&
                    moduloApi.excluir(m.id).then(carregar).catch((e) => setErro(mensagemErro(e)))}
                >
                  Excluir
                </button>
              </td>
            </tr>
          ))}
          {modulos.length === 0 && <tr><td colSpan={6} className="muted centro">Nenhum módulo cadastrado.</td></tr>}
        </tbody>
      </table>

      {editando && (
        <Modal titulo={editando === 'novo' ? 'Novo módulo' : 'Editar módulo'} onFechar={() => setEditando(null)}>
          <ModuloForm modulo={editando === 'novo' ? undefined : editando} onCancelar={() => setEditando(null)} onSalvo={() => { setEditando(null); carregar(); }} />
        </Modal>
      )}
    </>
  );
}
