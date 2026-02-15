import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 80,
    watch: {
      usePolling: true
    },
    proxy: {
      '/api/auth': {
        target: 'http://auth-service:8001',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api\/auth/, '/api')
      },
      '/api/tournaments': {
        target: 'http://tournament-service:8002',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api\/tournaments/, '/api')
      },
      '/api/teams': {
        target: 'http://team-service:8003',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api\/teams/, '/api')
      },
      '/api/matches': {
        target: 'http://match-service:8004',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api\/matches/, '/api')
      },
      '/api/results': {
        target: 'http://results-service:8005',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api\/results/, '/api')
      }
    }
  }
})
