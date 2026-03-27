import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  build: {
    outDir: 'assets',
    emptyOutDir: false,
    rollupOptions: {
      input: {
        style: 'assets/css/arva-seo-source.css',
      },
      output: {
        assetFileNames: 'css/arva-seo.css',
      },
    },
  },
});
