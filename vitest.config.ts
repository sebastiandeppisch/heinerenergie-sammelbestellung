import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';

/**
 * Deliberately separate from vite.config.js: the Laravel plugin wants an .env and
 * writes a hot file, neither of which a unit test run should depend on.
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        // Same form as vite.config.js, which keeps the two aliases in step
        alias: {
            '@': '/resources/js',
        },
    },
    test: {
        environment: 'jsdom',
        setupFiles: ['./tests/js/setup.ts'],
        include: ['tests/js/**/*.test.ts'],
        restoreMocks: true,
    },
});
