import { defineConfig } from 'vite';
import appPlugin from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        appPlugin({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
