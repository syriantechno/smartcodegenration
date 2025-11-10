import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ command }) => ({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/form-designer-new.js'
            ],
            refresh: true,
        }),
    ],
    css: {
        postcss: './postcss.config.cjs',
    },
}));
