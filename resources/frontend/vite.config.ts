import adapter from '@sveltejs/adapter-node';
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

// Dev/preview proxy: the Laravel API is served by the docker nginx-proxy,
// which routes by the Host header (VIRTUAL_HOST=shopera.test).
const apiProxy = {
	'/api': {
		target: process.env.API_PROXY_TARGET || 'http://127.0.0.1:80',
		changeOrigin: false,
		headers: { host: process.env.API_PROXY_HOST || 'shopera.test' }
	}
};

export default defineConfig({
	cacheDir: process.env.VITE_CACHE_DIR || '/tmp/shopera-vite-cache',
	plugins: [
		sveltekit({
			compilerOptions: {
				// Force runes mode for the project, except for libraries. Can be removed in svelte 6.
				runes: ({ filename }) =>
					filename.split(/[/\\]/).includes('node_modules') ? undefined : true
			},

			// adapter-auto only supports some environments, see https://svelte.dev/docs/kit/adapter-auto for a list.
			// If your environment is not supported, or you settled on a specific environment, switch out the adapter.
			// See https://svelte.dev/docs/kit/adapters for more information about adapters.
			adapter: adapter({ out: 'build' })
		})
	],
	build: {
		// Bundle every stylesheet into one file so the SPA shell links it up
		// front and the first paint is never unstyled.
		cssCodeSplit: false
	},
	server: { proxy: apiProxy },
	preview: { proxy: apiProxy }
});
