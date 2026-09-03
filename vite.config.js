import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/app.css',
            'resources/js/app.ts',
        ]),
        vue({
         template: {
                 transformAssetUrls: {
                     base: null,
                     includeAbsolute: false,
                 },
             },
        }),
        tailwindcss()
    ],
	resolve: {
        alias: {
            '@': '/resources/js'
        }
    },
    build: {
        sourcemap: true,
    },
    define: {
        "process.env": {},
    },
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost'
        }
    }
});