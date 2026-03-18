import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: process.env.VITE_DEV_SERVER_URL ?? 'http://webguard.test:5173',
        cors: {
            origin: process.env.APP_URL ?? 'http://webguard.test',
        },
        hmr: {
            host: process.env.VITE_HMR_HOST ?? 'webguard.test',
            port: 5173,
            clientPort: 5173,
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
            ],
            refresh: true
        }),
        tailwindcss(),
    ],
});
