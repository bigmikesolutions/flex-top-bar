import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'happy-dom',
    globals: true,
    root: '.',
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      'vue': path.resolve(__dirname, '../../node_modules/vue'),
    },
  },
})
