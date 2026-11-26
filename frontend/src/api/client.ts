export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors: Record<string, string[]> = {},
  ) {
    super(message);
  }
}

export async function request<T>(method: string, url: string, body?: unknown): Promise<T> {
  const res = await fetch(`/api${url}`, {
    method,
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (res.status === 204) return undefined as T;

  const json = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new ApiError(json.message ?? `Erro ${res.status}`, res.status, json.errors ?? {});
  }

  return json.data as T;
}

/** Upload multipart (não define Content-Type: o navegador coloca o boundary). */
export async function upload<T>(url: string, form: FormData): Promise<T> {
  const res = await fetch(`/api${url}`, { method: 'POST', headers: { Accept: 'application/json' }, body: form });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new ApiError(json.message ?? `Erro ${res.status}`, res.status, json.errors ?? {});
  return json.data as T;
}

export function mensagemErro(e: unknown, padrao = 'Erro inesperado.'): string {
  return e instanceof ApiError ? e.message : padrao;
}
