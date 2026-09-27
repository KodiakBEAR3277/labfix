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
        host: '192.168.1.12', 
        port: 5173,
        hmr: {
            host: '192.168.1.12',
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