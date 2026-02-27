import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'fs';
import path from 'path';

const host = process.env.VITE_HMR_HOST || 'localhost';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        // Custom plugin to rewrite hot file with network host (after Laravel plugin writes it)
        {
            name: 'network-hot-file',
            configureServer(server) {
                const hotFile = path.resolve(__dirname, 'public/hot');
                server.httpServer?.once('listening', () => {
                    // Wait a tick for Laravel plugin to write its hot file first
                    setTimeout(() => {
                        const address = server.httpServer?.address();
                        const port = typeof address === 'object' ? address?.port : 5173;
                        fs.writeFileSync(hotFile, `http://${host}:${port}`);
                    }, 100);
                });
            },
        },
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: host,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
