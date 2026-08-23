import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],
    // 1. Add the server block to expose Vite to your local network
    server: {
        host: '172.17.0.165', 
        port: 5173,
        hmr: {
            host: '172.17.0.165',
        },
        cors: true,
    },
    resolve: {
        alias: {
            // Lets you write: import NavLanding from '@/Components/Nav/NavLanding.vue'
            // instead of a long relative path
            '@': '/resources/js',
        },
    },
});