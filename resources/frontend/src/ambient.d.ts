// Ambient declarations for modules/packages that ship without types.


declare module 'bootstrap/js/dist/modal' {
	export default class Modal {
		constructor(element: Element, options?: Record<string, unknown>);
		show(): void;
		hide(): void;
		dispose(): void;
	}
}

// `vite.config.ts` reads proxy settings from the environment.
declare const process: { env: Record<string, string | undefined> };
