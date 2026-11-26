import { useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { mensagemErro } from '../api/client';
import { cofreApi, moduloApi } from '../api/produtoApi';
import { AreaUpload } from '../components/AreaUpload';
import { Campo } from '../components/Campo';
import { Modal } from '../components/Modal';
import { ModuloBadge } from '../components/ModuloBadge';
import { formatarBytes, formatarData } from '../lib/util';
import type { Categoria, Documento, Modulo } from '../types';

const ICONE_EXT: Record<string, string> = {
  pdf: '📕', doc: '📘', docx: '📘', xls: '📗', xlsx: '📗', csv: '📗', ppt: '📙', pptx: '📙',
  png: '🖼', jpg: '🖼', jpeg: '🖼', gif: '🖼', webp: '🖼', zip: '🗜', txt: '📄', md: '📄', xml: '📄', json: '📄',
};

type FiltroModulo = number | 'todos' | 'sem';

// ------------------------------------------------------------ upload avulso
function EnviarDocumentos({ categorias, modulos, moduloInicial, onConcluido }: {
  categorias: Categoria[]; modulos: Modulo[]; moduloInicial: number | null; onConcluido: () => void;
}) {
  const [arquivos, setArquivos] = useState<File[]>([]);
  const [d, setD] = useState({ categoria_id: categorias[0]?.id ?? 0, modulo_id: moduloInicial ?? ('' as number | ''), tags: '', descricao: '', enviado_por: '' });
  const [status, setStatus] = useState<Record<string, string>>({});
  const [enviando, setEnviando] = useState(false);

  async function enviar() {
    setEnviando(true);
    let falhas = 0;
    for (const a of arquivos) {
      setStatus((s) => ({ ...s, [a.name]: 'enviando…' }));
      try {
        await cofreApi.enviar(a, { ...d, modulo_id: d.modulo_id || null });
        setStatus((s) => ({ ...s, [a.name]: '✓ enviado' }));
      } catch (e) {
        falhas++;
        setStatus((s) => ({ ...s, [a.name]: `✗ ${mensagemErro(e)}` }));
      }
    }
    setEnviando(false);
    if (falhas === 0) onConcluido();
  }

  return (
    <div className="form">
      <AreaUpload onArquivos={(n) => setArquivos((a) => [...a, ...n])} desabilitado={enviando} />
      {arquivos.length > 0 && (
        <ul className="lista-arquivos">
          {arquivos.map((a) => (
            <li key={a.name}>
              <span>{a.name} <span className="muted">({formatarBytes(a.size)})</span></span>
              <span className="pequeno">{status[a.name] ?? (
                <button className="btn-icone" onClick={() => setArquivos((x) => x.filter((y) => y !== a))}>×</button>
              )}</span>
            </li>
          ))}
        </ul>
      )}
      <div className="linha">
        <Campo rotulo="Categoria" obrigatorio>
          <select value={d.categoria_id} onChange={(e) => setD({ ...d, categoria_id: Number(e.target.value) })}>
            {categorias.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
          </select>
        </Campo>
        <Campo rotulo="Módulo (tag)">
          <select value={d.modulo_id} onChange={(e) => setD({ ...d, modulo_id: Number(e.target.value) || '' })}>
            <option value="">Geral (sem módulo)</option>
            {modulos.map((m) => <option key={m.id} value={m.id}>{m.tag} · {m.nome}</option>)}
          </select>
        </Campo>
      </div>
      <div className="linha">
        <Campo rotulo="Tags extras">
          <input value={d.tags} onChange={(e) => setD({ ...d, tags: e.target.value })} placeholder="nfce, sefaz, nt-2025 (separadas por vírgula)" />
        </Campo>
        <Campo rotulo="Enviado por">
          <input value={d.enviado_por} onChange={(e) => setD({ ...d, enviado_por: e.target.value })} />
        </Campo>
      </div>
      <Campo rotulo="Descrição">
        <textarea rows={2} value={d.descricao} onChange={(e) => setD({ ...d, descricao: e.target.value })} />
      </Campo>
      <footer className="form-acoes">
        <button className="btn btn-primario" disabled={enviando || arquivos.length === 0 || !d.categoria_id} onClick={enviar}>
          {enviando ? 'Enviando…' : `Enviar ${arquivos.length || ''} arquivo(s) para o cofre`}
        </button>
      </footer>
    </div>
  );
}

// ------------------------------------------------------------ editar metadados
function EditarDocumento({ doc, categorias, modulos, onSalvo }: { doc: Documento; categorias: Categoria[]; modulos: Modulo[]; onSalvo: () => void }) {
  const [d, setD] = useState({ titulo: doc.titulo, descricao: doc.descricao ?? '', categoria_id: doc.categoria_id, modulo_id: doc.modulo_id ?? ('' as number | ''), tags: doc.tags.join(', ') });
  const [erro, setErro] = useState('');

  const salvar = () =>
    cofreApi
      .atualizar(doc.id, { ...d, descricao: d.descricao || null, modulo_id: d.modulo_id || null, tags: d.tags.split(',').map((t) => t.trim()).filter(Boolean) })
      .then(onSalvo)
      .catch((e) => setErro(mensagemErro(e)));

  return (
    <div className="form">
      {erro && <div className="alerta erro">{erro}</div>}
      <Campo rotulo="Título" obrigatorio><input value={d.titulo} onChange={(e) => setD({ ...d, titulo: e.target.value })} /></Campo>
      <div className="linha">
        <Campo rotulo="Categoria">
          <select value={d.categoria_id} onChange={(e) => setD({ ...d, categoria_id: Number(e.target.value) })}>
            {categorias.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
          </select>
        </Campo>
        <Campo rotulo="Módulo">
          <select value={d.modulo_id} disabled={!!doc.regra_id} onChange={(e) => setD({ ...d, modulo_id: Number(e.target.value) || '' })}>
            <option value="">Geral (sem módulo)</option>
            {modulos.map((m) => <option key={m.id} value={m.id}>{m.tag}</option>)}
          </select>
        </Campo>
      </div>
      <Campo rotulo="Tags"><input value={d.tags} onChange={(e) => setD({ ...d, tags: e.target.value })} /></Campo>
      <Campo rotulo="Descrição"><textarea rows={3} value={d.descricao} onChange={(e) => setD({ ...d, descricao: e.target.value })} /></Campo>
      <footer className="form-acoes"><button className="btn btn-primario" onClick={salvar}>Salvar</button></footer>
    </div>
  );
}

// ------------------------------------------------------------ categorias
function GerenciarCategorias({ categorias, onAlterado }: { categorias: Categoria[]; onAlterado: () => void }) {
  const [nome, setNome] = useState('');
  const [erro, setErro] = useState('');
  const acao = (p: Promise<unknown>) => p.then(() => { setErro(''); onAlterado(); }).catch((e) => setErro(mensagemErro(e)));

  return (
    <div className="form">
      {erro && <div className="alerta erro">{erro}</div>}
      <ul className="lista-simples">
        {categorias.map((c) => (
          <li key={c.id} className="linha-entre">
            <span>{c.nome} <span className="muted pequeno">({c.total_documentos ?? 0})</span></span>
            <button className="btn btn-pequeno btn-perigo" onClick={() => acao(cofreApi.excluirCategoria(c.id))}>Excluir</button>
          </li>
        ))}
      </ul>
      <div className="linha-inline">
        <input value={nome} placeholder="Nova categoria" onChange={(e) => setNome(e.target.value)} />
        <button className="btn btn-primario" disabled={!nome.trim()} onClick={() => acao(cofreApi.salvarCategoria(null, { nome }).then(() => setNome('')))}>Adicionar</button>
      </div>
    </div>
  );
}

// ------------------------------------------------------------ página
export function CofrePage() {
  const [params] = useSearchParams();
  const [docs, setDocs] = useState<Documento[]>([]);
  const [todosDocs, setTodosDocs] = useState<Documento[]>([]);
  const [categorias, setCategorias] = useState<Categoria[]>([]);
  const [modulos, setModulos] = useState<Modulo[]>([]);
  const [moduloSel, setModuloSel] = useState<FiltroModulo>(params.get('modulo') ? Number(params.get('modulo')) : 'todos');
  const [categoriaSel, setCategoriaSel] = useState<number | ''>('');
  const [busca, setBusca] = useState('');
  const [modal, setModal] = useState<'enviar' | 'categorias' | Documento | null>(null);
  const [erro, setErro] = useState('');

  const carregarBase = () => {
    cofreApi.categorias().then(setCategorias);
    moduloApi.listar().then(setModulos);
    cofreApi.listar().then(setTodosDocs);
  };

  const carregar = () =>
    cofreApi
      .listar({
        busca,
        categoria_id: categoriaSel,
        modulo_id: typeof moduloSel === 'number' ? moduloSel : '',
        sem_modulo: moduloSel === 'sem',
      })
      .then(setDocs);

  useEffect(carregarBase, []);
  useEffect(() => {
    const t = setTimeout(carregar, 250);
    return () => clearTimeout(t);
  }, [busca, categoriaSel, moduloSel]); // eslint-disable-line react-hooks/exhaustive-deps

  const recarregarTudo = () => { carregarBase(); carregar(); };

  const contagemModulo = (id: number | null) => todosDocs.filter((d) => d.modulo_id === id).length;
  const modulo = (id: number | null) => modulos.find((m) => m.id === id) ?? null;
  const categoria = (id: number) => categorias.find((c) => c.id === id)?.nome ?? '—';

  // agrupado por módulo (departamento) quando "Todos"
  const grupos = useMemo(() => {
    if (moduloSel !== 'todos') return [{ modulo: typeof moduloSel === 'number' ? modulo(moduloSel) : null, docs }];
    const mapa = new Map<number | null, Documento[]>();
    docs.forEach((d) => mapa.set(d.modulo_id, [...(mapa.get(d.modulo_id) ?? []), d]));
    return [...mapa.entries()]
      .map(([id, lista]) => ({ modulo: modulo(id), docs: lista }))
      .sort((a, b) => (a.modulo?.tag ?? 'ZZZ').localeCompare(b.modulo?.tag ?? 'ZZZ'));
  }, [docs, moduloSel, modulos]); // eslint-disable-line react-hooks/exhaustive-deps

  return (
    <>
      <div className="topo-pagina">
        <div>
          <h1>Cofre de documentos e normas técnicas</h1>
          <p className="muted">Documentos departamentalizados pela tag de cada módulo. Anexos das regras de negócio entram aqui automaticamente.</p>
        </div>
        <div className="acoes">
          <button className="btn" onClick={() => setModal('categorias')}>Categorias</button>
          <button className="btn btn-primario" onClick={() => setModal('enviar')}>⇪ Enviar documentos</button>
        </div>
      </div>

      <div className="cofre">
        <aside className="cofre-modulos">
          <button className={moduloSel === 'todos' ? 'ativo' : ''} onClick={() => setModuloSel('todos')}>
            <span>Todos os módulos</span><span className="muted">{todosDocs.length}</span>
          </button>
          {modulos.map((m) => (
            <button key={m.id} className={moduloSel === m.id ? 'ativo' : ''} onClick={() => setModuloSel(m.id)}>
              <span><i className="bolinha" style={{ background: m.cor }} /> {m.tag}</span><span className="muted">{contagemModulo(m.id)}</span>
            </button>
          ))}
          <button className={moduloSel === 'sem' ? 'ativo' : ''} onClick={() => setModuloSel('sem')}>
            <span>Geral (sem módulo)</span><span className="muted">{contagemModulo(null)}</span>
          </button>
        </aside>

        <section>
          <div className="filtros">
            <input placeholder="Buscar por título, arquivo, descrição ou tag…" value={busca} onChange={(e) => setBusca(e.target.value)} />
            <select value={categoriaSel} onChange={(e) => setCategoriaSel(Number(e.target.value) || '')}>
              <option value="">Todas as categorias</option>
              {categorias.map((c) => <option key={c.id} value={c.id}>{c.nome}</option>)}
            </select>
          </div>
          {erro && <div className="alerta erro">{erro}</div>}

          {docs.length === 0 && <div className="vazio"><p>Nenhum documento encontrado.</p></div>}

          {grupos.map((g) => (
            <div key={g.modulo?.id ?? 'geral'} className="grupo-cofre">
              {moduloSel === 'todos' && (
                <h3 className="linha-inline"><ModuloBadge modulo={g.modulo} /> <span className="muted pequeno">{g.modulo?.nome ?? 'Documentos gerais'} · {g.docs.length}</span></h3>
              )}
              <table className="tabela">
                <thead>
                  <tr><th>Documento</th><th style={{ width: 170 }}>Categoria</th><th style={{ width: 150 }}>Origem</th><th style={{ width: 120 }}>Data</th><th style={{ width: 230 }} /></tr>
                </thead>
                <tbody>
                  {g.docs.map((d) => (
                    <tr key={d.id}>
                      <td>
                        <div className="linha-inline">
                          <span className="icone-arquivo">{ICONE_EXT[d.arquivo.extensao] ?? '📄'}</span>
                          <div>
                            <strong>{d.titulo}</strong>
                            <div className="muted pequeno">{d.arquivo.nome_original} · {formatarBytes(d.arquivo.tamanho)}{d.enviado_por && ` · ${d.enviado_por}`}</div>
                            {d.tags.length > 0 && <div>{d.tags.map((t) => <span key={t} className="chip">#{t}</span>)}</div>}
                          </div>
                        </div>
                      </td>
                      <td>{categoria(d.categoria_id)}</td>
                      <td className="pequeno">
                        {d.regra_id ? <Link to={`/regras/${d.regra_id}`} className="link">Regra #{d.regra_id}</Link> : <span className="muted">{d.origem_rotulo}</span>}
                      </td>
                      <td className="muted pequeno">{formatarData(d.criado_em, false)}</td>
                      <td className="acoes-linha">
                        <a className="btn btn-pequeno" href={cofreApi.urlDownload(d.id, true)} target="_blank" rel="noreferrer">Abrir</a>
                        <a className="btn btn-pequeno" href={cofreApi.urlDownload(d.id)}>Baixar</a>
                        <button className="btn btn-pequeno" onClick={() => setModal(d)}>Editar</button>
                        <button
                          className="btn btn-pequeno btn-perigo"
                          onClick={() => window.confirm(`Excluir "${d.titulo}" do cofre? O arquivo será apagado.`) &&
                            cofreApi.excluir(d.id).then(recarregarTudo).catch((e) => setErro(mensagemErro(e)))}
                        >
                          ×
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ))}
        </section>
      </div>

      {modal === 'enviar' && (
        <Modal titulo="Enviar documentos para o cofre" onFechar={() => setModal(null)} largo>
          <EnviarDocumentos
            categorias={categorias}
            modulos={modulos}
            moduloInicial={typeof moduloSel === 'number' ? moduloSel : null}
            onConcluido={() => { setModal(null); recarregarTudo(); }}
          />
        </Modal>
      )}
      {modal === 'categorias' && (
        <Modal titulo="Categorias de documento" onFechar={() => setModal(null)}>
          <GerenciarCategorias categorias={categorias} onAlterado={carregarBase} />
        </Modal>
      )}
      {modal && typeof modal === 'object' && (
        <Modal titulo="Editar documento" onFechar={() => setModal(null)}>
          <EditarDocumento doc={modal} categorias={categorias} modulos={modulos} onSalvo={() => { setModal(null); recarregarTudo(); }} />
        </Modal>
      )}
    </>
  );
}
