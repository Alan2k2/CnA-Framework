import { defineConfig } from 'vite';

export default defineConfig({
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
    origin: 'http://127.0.0.1:5173',
  },
  build: {
    outDir: 'public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: [
        'resources/js/app.ts',
        'resources/css/app.css',
        'resources/modules/dashboard/index.ts',
      ],
    },
  },
});

