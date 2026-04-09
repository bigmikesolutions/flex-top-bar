import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig(({ mode }) => {
  const isDistBuild = mode === 'dist'

  return {
    root: __dirname,
    plugins: [vue()],
    build: {
      cssCodeSplit: false,
      outDir: isDistBuild ? 'assets/dist-dev' : 'assets/dist',
      emptyOutDir: true,
      // For the "dist" (debuggable) build we explicitly disable minification.
      // For the package build we keep Vite's default minifier/toolchain to avoid
      // requiring an explicit `esbuild` dependency.
      minify: isDistBuild ? false : undefined,
      rollupOptions: {
        input: {
          admin: path.resolve(__dirname, 'src/main.ts'),
          frontend: path.resolve(__dirname, 'src/frontend.ts'),
        },
        output: {
          entryFileNames: 'js/[name].js',
          chunkFileNames: 'js/[name]-[hash].js',
          assetFileNames: (assetInfo) => {
            if (assetInfo.name?.endsWith('.css')) {
              return 'css/[name][extname]'
            }
            return 'assets/[name]-[hash][extname]'
          },
        },
      },
    },
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
    define: {
      __VUE_OPTIONS_API__: false,
      __VUE_PROD_DEVTOOLS__: false,
    },
  }
})
