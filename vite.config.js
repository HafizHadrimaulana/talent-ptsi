import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/app-layout.css',
                'resources/css/app-ui.css',
                'resources/js/app.js',
                'resources/js/app-layout.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});