import { defineConfig } from 'vite' 
import vue from '@vitejs/plugin-vue' 
 
export default defineConfig({ 
  plugins: [vue()], 
  resolve: { 
    alias: { 
      '@': '/src/js' 
    } 
  },
  optimizeDeps: {
    include: ['ethers']
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('vue') || id.includes('vue-router') || id.includes('vuex')) {
              return 'vue-vendor'
            }
            if (id.includes('ethers')) {
              return 'blockchain'
            }
            if (id.includes('axios')) {
              return 'utils'
            }
            return 'vendor'
          }
        }
      }
    },
    chunkSizeWarningLimit: 1000
  },
  server: { 
    port: 3000, 
    proxy: { 
      '/api': { 
        target: 'http://127.0.0.1:9001', 
        changeOrigin: true,
        secure: false
      } 
    } 
  } 
}) 
