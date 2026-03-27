import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  // Ensure built asset URLs work from within a WP plugin directory
  base: './',
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
        assetFileNames: (assetInfo) => {
          const name = assetInfo?.name ?? '';
          if (name.endsWith('.css')) return 'css/arva-seo.css';
          if (/\.(woff2?|ttf|otf|eot)$/i.test(name)) return 'fonts/[name][extname]';
          return '[name][extname]';
        },
      },
    },
  },
});
