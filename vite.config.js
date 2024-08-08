import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel([
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
    ],
	resolve: {
        alias: {
            '@': '/resources/js'
        }
    },
    define: {
        "process.env": {},
    },
});