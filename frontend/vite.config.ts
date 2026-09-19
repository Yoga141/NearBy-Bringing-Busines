import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import path from 'node:path'

// https://vite.dev/config/
export default defineConfig({
  base: '/',
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  server: {
    // Forward API calls to the local Laravel backend (`php artisan serve`,
    // default port 8000) during development. In production the built SPA
    // and the API are deployed under the same domain (see .cpanel.yml), so
    // `/api/*` is same-origin there and no proxy is involved.
    proxy: {
      // The voice assistant's NLP runs in the Python service (`nlp-service/`,
      // `jalankan.bat`, port 8001). Must come before '/api': the first
      // matching prefix wins.
      '/api/voice-nlp': {
        target: 'http://127.0.0.1:8001',
        changeOrigin: true,
      },
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
