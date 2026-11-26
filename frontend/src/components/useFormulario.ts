import { useState } from 'react';
import { ApiError } from '../api/client';

/** Estado de envio + erros vindos da API (validação 422 e regras de domínio). */
export function useFormulario() {
  const [salvando, setSalvando] = useState(false);
  const [erros, setErros] = useState<Record<string, string[]>>({});
  const [mensagem, setMensagem] = useState('');

  async function enviar(acao: () => Promise<void>) {
    setSalvando(true);
    setErros({});
    setMensagem('');
    try {
      await acao();
    } catch (e) {
      if (e instanceof ApiError) {
        setErros(e.errors);
        if (!Object.keys(e.errors).length) setMensagem(e.message);
      } else {
        setMensagem('Não foi possível conectar à API.');
      }
    } finally {
      setSalvando(false);
    }
  }

  return { salvando, erros, mensagem, enviar };
}
