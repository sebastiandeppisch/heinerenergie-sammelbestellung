import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel([
            'resources/css/app.css',
            'resources/js/app.ts',
            'resources/js/helper.ts'
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
    define: {
        "process.env": {},
    },
    server: {
        host: 'localhost'
    }
});