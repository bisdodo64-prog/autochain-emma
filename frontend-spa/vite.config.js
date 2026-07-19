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
