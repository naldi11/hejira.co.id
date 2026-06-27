import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import svgr from 'vite-plugin-svgr';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        cors: true,
    },
    plugins: [
        tailwindcss(),
        laravel({
            // app.js  → legacy Alpine/Blade pages (still used by un-migrated modules)
            // app.jsx → Inertia + React pages (migrated modules)
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/app.jsx'],
            refresh: true,
        }),
        svgr(),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
