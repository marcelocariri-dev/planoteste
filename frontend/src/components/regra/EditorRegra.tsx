import { useMemo, useRef, useState } from 'react';
import { renderizarRegra } from '../../lib/markdown';
import type { AnaliseRegra, TipoMarcador } from '../../types';
import { ICONE_MARCADOR } from '../RegraBadges';

interface Props {
  valor: string;
  onChange: (v: string) => void;
  somenteLeitura: boolean;
  analise: AnaliseRegra | null;
}

type Modo = 'dividir' | 'editar' | 'visualizar';

const MODELO = `## Contexto
Descreva o cenário atual e o problema.

## Objetivo
O que a melhoria/novo módulo deve resolver.

## Escopo
**Dentro do escopo:**
-

**Fora do escopo:**
-

## Regras de negócio
::: regra RN01 - Nome da regra
Descrição da regra.
:::

## Critérios de aceite
::: criterio
Dado ... Quando ... Então ...
:::

## Casos de teste
::: caso [alta] Título do caso
Pré-condições:
Passos:
1.
Resultado esperado:
:::

## Exceções
::: excecao
Comportamento em situações de exceção.
:::

## Dúvidas em aberto
::: duvida
Pergunta para PM/PO.
:::
`;

const TAGS: { tipo: TipoMarcador; rotulo: string; dica: string }[] = [
  { tipo: 'caso', rotulo: 'Caso', dica: 'Marca o trecho como caso de teste (vira caso no plano)' },
  { tipo: 'regra', rotulo: 'Regra', dica: 'Regra de negócio (RN)' },
  { tipo: 'criterio', rotulo: 'Critério', dica: 'Critério de aceite' },
  { tipo: 'excecao', rotulo: 'Exceção', dica: 'Fluxo de exceção' },
  { tipo: 'duvida', rotulo: 'Dúvida', dica: 'Dúvida para PM/PO responder' },
];

/** Verificações rápidas de completude do documento. */
function checklist(texto: string, a: AnaliseRegra | null) {
  const c = a?.contagem_marcadores;
  return [
    { ok: /^#+\s*contexto/im.test(texto), rotulo: 'Seção "Contexto"' },
    { ok: /^#+\s*objetivo/im.test(texto), rotulo: 'Seção "Objetivo"' },
    { ok: (c?.regra ?? 0) > 0, rotulo: 'Pelo menos 1 regra marcada' },
    { ok: (c?.criterio ?? 0) > 0, rotulo: 'Critérios de aceite' },
    { ok: (c?.caso ?? 0) > 0, rotulo: 'Casos de teste marcados' },
    { ok: (c?.duvida ?? 0) === 0, rotulo: c?.duvida ? `${c.duvida} dúvida(s) em aberto` : 'Sem dúvidas em aberto' },
  ];
}

export function EditorRegra({ valor, onChange, somenteLeitura, analise }: Props) {
  const ref = useRef<HTMLTextAreaElement>(null);
  const [modo, setModo] = useState<Modo>(somenteLeitura ? 'visualizar' : 'dividir');
  const html = useMemo(() => renderizarRegra(valor), [valor]);

  /** Substitui a seleção e depois seleciona [selIni, selFim] relativos ao texto inserido. */
  function inserir(texto: string, selIni = texto.length, selFim = selIni) {
    const ta = ref.current;
    if (!ta) return;
    const { selectionStart: s, selectionEnd: e } = ta;
    onChange(valor.slice(0, s) + texto + valor.slice(e));
    requestAnimationFrame(() => {
      ta.focus();
      ta.setSelectionRange(s + selIni, s + selFim);
    });
  }

  function envolver(antes: string, depois: string, exemplo: string) {
    const ta = ref.current!;
    const sel = valor.slice(ta.selectionStart, ta.selectionEnd) || exemplo;
    inserir(antes + sel + depois, antes.length, antes.length + sel.length);
  }

  function prefixarLinhas(prefixo: (i: number) => string) {
    const ta = ref.current!;
    const ini = valor.lastIndexOf('\n', ta.selectionStart - 1) + 1;
    ta.setSelectionRange(ini, ta.selectionEnd);
    const sel = valor.slice(ini, ta.selectionEnd) || 'item';
    inserir(sel.split('\n').map((l, i) => prefixo(i) + l).join('\n'));
  }

  function marcar(tipo: TipoMarcador) {
    const ta = ref.current!;
    const { selectionStart: s, selectionEnd: e } = ta;
    const sel = valor.slice(s, e).trim();
    const quebraAntes = s > 0 && valor[s - 1] !== '\n' ? '\n' : '';
    const abre = `${quebraAntes}::: ${tipo}${tipo === 'caso' ? ' [media]' : ''} `;

    if (sel) {
      // primeira linha selecionada vira o título; o resto vira o corpo do bloco
      const [primeira, ...resto] = sel.split('\n');
      const titulo = primeira.replace(/^[#>*\-\d.\s]+/, '').trim().slice(0, 120);
      const corpo = resto.join('\n').trim();
      inserir(`${abre}${titulo}\n${corpo ? corpo + '\n' : ''}:::\n`, abre.length, abre.length + titulo.length);
      return;
    }

    const titulo = tipo === 'caso' ? 'Título do caso' : 'Título';
    const corpo = tipo === 'caso' ? 'Pré-condições: \nPassos:\n1. \nResultado esperado: ' : 'Descreva aqui.';
    inserir(`${abre}${titulo}\n${corpo}\n:::\n`, abre.length, abre.length + titulo.length);
  }

  function irParaLinha(linha: number) {
    if (modo === 'visualizar') setModo('dividir');
    const pos = valor.split('\n').slice(0, linha - 1).join('\n').length + (linha > 1 ? 1 : 0);
    requestAnimationFrame(() => {
      const ta = ref.current;
      if (!ta) return;
      ta.focus();
      ta.setSelectionRange(pos, pos);
      const alturaLinha = parseFloat(getComputedStyle(ta).lineHeight) || 20;
      ta.scrollTop = Math.max(0, (linha - 3) * alturaLinha);
    });
  }

  const itens = checklist(valor, analise);

  return (
    <div className="editor-regra">
      <div className="editor-barra">
        {!somenteLeitura && modo !== 'visualizar' && (
          <>
            <div className="grupo-ferramentas">
              <button type="button" title="Título" onClick={() => prefixarLinhas(() => '## ')}>H</button>
              <button type="button" title="Negrito" onClick={() => envolver('**', '**', 'texto')}><b>B</b></button>
              <button type="button" title="Itálico" onClick={() => envolver('_', '_', 'texto')}><i>I</i></button>
              <button type="button" title="Lista" onClick={() => prefixarLinhas(() => '- ')}>•</button>
              <button type="button" title="Lista numerada" onClick={() => prefixarLinhas((i) => `${i + 1}. `)}>1.</button>
              <button type="button" title="Checklist" onClick={() => prefixarLinhas(() => '- [ ] ')}>☐</button>
              <button type="button" title="Tabela" onClick={() => inserir('\n| Campo | Regra |\n|---|---|\n|  |  |\n')}>▦</button>
            </div>
            <div className="grupo-ferramentas grupo-tags">
              <span className="muted pequeno">Marcar como:</span>
              {TAGS.map((t) => (
                <button type="button" key={t.tipo} className={`tag-${t.tipo}`} title={t.dica} onClick={() => marcar(t.tipo)}>
                  {ICONE_MARCADOR[t.tipo]} {t.rotulo}
                </button>
              ))}
            </div>
            <button type="button" className="btn btn-pequeno" title="Inserir a estrutura padrão do documento" onClick={() => inserir(MODELO)}>
              Modelo
            </button>
          </>
        )}
        <div className="grupo-modo">
          {(['editar', 'dividir', 'visualizar'] as Modo[]).filter((m) => !somenteLeitura || m === 'visualizar').map((m) => (
            <button type="button" key={m} className={modo === m ? 'ativo' : ''} onClick={() => setModo(m)}>
              {m === 'editar' ? 'Editor' : m === 'dividir' ? 'Lado a lado' : 'Visualizar'}
            </button>
          ))}
        </div>
      </div>

      <div className="editor-corpo">
        <div className={`editor-areas modo-${modo}`}>
          {modo !== 'visualizar' && (
            <textarea
              ref={ref}
              className="editor-texto"
              value={valor}
              readOnly={somenteLeitura}
              spellCheck
              onChange={(e) => onChange(e.target.value)}
              placeholder={'Escreva a regra em markdown.\nSelecione um trecho e use "Marcar como" para separar casos, regras, critérios, exceções e dúvidas.\nOu clique em "Modelo" para começar com a estrutura padrão.'}
            />
          )}
          {modo !== 'editar' && (
            <div className="editor-preview markdown" dangerouslySetInnerHTML={{ __html: html || '<p class="muted">Nada escrito ainda.</p>' }} />
          )}
        </div>

        <aside className="editor-lateral">
          <h4>Marcadores</h4>
          {!analise || analise.marcadores.length === 0 ? (
            <p className="muted pequeno">Nenhum trecho marcado.</p>
          ) : (
            <ul className="lista-marcadores">
              {analise.marcadores.map((m, i) => (
                <li key={i} className={`tag-${m.tipo}`} onClick={() => irParaLinha(m.linha)} title={`Ir para a linha ${m.linha}`}>
                  <span>{ICONE_MARCADOR[m.tipo]}</span>
                  <span className="titulo-marcador">{m.titulo || m.tipo_rotulo}</span>
                </li>
              ))}
            </ul>
          )}
          <h4>Qualidade do documento</h4>
          <ul className="checklist">
            {itens.map((i) => (
              <li key={i.rotulo} className={i.ok ? 'ok' : 'pendente'}>{i.ok ? '✓' : '○'} {i.rotulo}</li>
            ))}
          </ul>
        </aside>
      </div>
    </div>
  );
}
