import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Dentro do docker a API é acessada pelo nome do serviço; fora, por localhost.
const apiUrl = process.env.VITE_API_PROXY ?? 'http://localhost:8090';

export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    port: 5173,
    watch: { usePolling: true }, // necessário para hot reload em volumes do WSL/Docker
    proxy: {
      '/api': { target: apiUrl, changeOrigin: true },
    },
  },
});
