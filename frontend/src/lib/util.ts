export const formatarBytes = (n: number) =>
  n < 1024 ? `${n} B` : n < 1024 ** 2 ? `${(n / 1024).toFixed(0)} KB` : `${(n / 1024 ** 2).toFixed(1)} MB`;

export const formatarData = (iso: string | null, comHora = true) =>
  iso ? new Date(iso).toLocaleString('pt-BR', comHora ? undefined : { dateStyle: 'short' }) : '—';

export function baixarTexto(nome: string, conteudo: string, tipo = 'text/plain;charset=utf-8') {
  const url = URL.createObjectURL(new Blob([conteudo], { type: tipo }));
  const a = Object.assign(document.createElement('a'), { href: url, download: nome });
  a.click();
  URL.revokeObjectURL(url);
}

export async function copiar(texto: string): Promise<boolean> {
  try {
    await navigator.clipboard.writeText(texto);
    return true;
  } catch {
    return false;
  }
}

/** Diff simples por linha (LCS) para comparar versões da regra. */
export function diffLinhas(antes: string, depois: string): { tipo: '=' | '+' | '-'; texto: string }[] {
  const a = antes.split('\n');
  const b = depois.split('\n');
  const n = a.length;
  const m = b.length;
  const lcs: number[][] = Array.from({ length: n + 1 }, () => new Array(m + 1).fill(0));
  for (let i = n - 1; i >= 0; i--)
    for (let j = m - 1; j >= 0; j--) lcs[i][j] = a[i] === b[j] ? lcs[i + 1][j + 1] + 1 : Math.max(lcs[i + 1][j], lcs[i][j + 1]);

  const r: { tipo: '=' | '+' | '-'; texto: string }[] = [];
  let i = 0;
  let j = 0;
  while (i < n && j < m) {
    if (a[i] === b[j]) { r.push({ tipo: '=', texto: a[i] }); i++; j++; }
    else if (lcs[i + 1][j] >= lcs[i][j + 1]) r.push({ tipo: '-', texto: a[i++] });
    else r.push({ tipo: '+', texto: b[j++] });
  }
  while (i < n) r.push({ tipo: '-', texto: a[i++] });
  while (j < m) r.push({ tipo: '+', texto: b[j++] });
  return r;
}
