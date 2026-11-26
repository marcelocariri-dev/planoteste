import { BrowserRouter, Link, Route, Routes } from 'react-router-dom';
import { PlanoDetalhePage } from './pages/PlanoDetalhePage';
import { PlanosPage } from './pages/PlanosPage';

export function App() {
  return (
    <BrowserRouter>
      <header className="barra-topo">
        <Link to="/" className="marca">▣ Plano de Teste</Link>
      </header>
      <main className="conteudo">
        <Routes>
          <Route path="/" element={<PlanosPage />} />
          <Route path="/planos/:id" element={<PlanoDetalhePage />} />
          <Route path="*" element={<p>Página não encontrada. <Link to="/">Voltar</Link></p>} />
        </Routes>
      </main>
    </BrowserRouter>
  );
}
