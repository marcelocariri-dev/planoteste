import { BrowserRouter, Link, NavLink, Route, Routes } from 'react-router-dom';
import { BacklogDetalhePage } from './pages/BacklogDetalhePage';
import { BacklogNovoPage } from './pages/BacklogNovoPage';
import { BacklogsPage } from './pages/BacklogsPage';
import { CofrePage } from './pages/CofrePage';
import { IntegracaoMcpPage } from './pages/IntegracaoMcpPage';
import { ModulosPage } from './pages/ModulosPage';
import { PlanoDetalhePage } from './pages/PlanoDetalhePage';
import { PlanosPage } from './pages/PlanosPage';
import { RegraPage } from './pages/RegraPage';
import { RegrasPage } from './pages/RegrasPage';

const MENU = [
  { secao: 'Qualidade', itens: [{ to: '/', rotulo: 'Planos de teste', icone: '▣', fim: true }] },
  {
    secao: 'Produto (PM | PO)',
    itens: [
      { to: '/regras', rotulo: 'Validação de regras', icone: '✎' },
      { to: '/backlog', rotulo: 'Product backlog', icone: '☰' },
    ],
  },
  { secao: 'Documentos', itens: [{ to: '/cofre', rotulo: 'Cofre de documentos', icone: '🗄' }] },
  { secao: 'Integrações', itens: [{ to: '/integracao-mcp', rotulo: 'Integração MCP', icone: '⇄' }] },
  { secao: 'Configuração', itens: [{ to: '/modulos', rotulo: 'Módulos', icone: '◈' }] },
];

export function App() {
  return (
    <BrowserRouter>
      <div className="layout">
        <aside className="sidebar">
          <Link to="/" className="marca">▣ Plano de Teste</Link>
          {MENU.map((g) => (
            <div key={g.secao} className="menu-grupo">
              <span className="menu-secao">{g.secao}</span>
              {g.itens.map((i) => (
                <NavLink key={i.to} to={i.to} end={'fim' in i} className={({ isActive }) => `menu-item ${isActive ? 'ativo' : ''}`}>
                  <span className="menu-icone">{i.icone}</span>
                  {i.rotulo}
                </NavLink>
              ))}
            </div>
          ))}
        </aside>
        <main className="conteudo">
          <Routes>
            <Route path="/" element={<PlanosPage />} />
            <Route path="/planos/:id" element={<PlanoDetalhePage />} />
            <Route path="/regras" element={<RegrasPage />} />
            <Route path="/regras/:id" element={<RegraPage />} />
            <Route path="/cofre" element={<CofrePage />} />
            <Route path="/backlog" element={<BacklogsPage />} />
            <Route path="/backlog/novo" element={<BacklogNovoPage />} />
            <Route path="/backlog/:id" element={<BacklogDetalhePage />} />
            <Route path="/integracao-mcp" element={<IntegracaoMcpPage />} />
            <Route path="/modulos" element={<ModulosPage />} />
            <Route path="*" element={<p>Página não encontrada. <Link to="/">Voltar</Link></p>} />
          </Routes>
        </main>
      </div>
    </BrowserRouter>
  );
}
