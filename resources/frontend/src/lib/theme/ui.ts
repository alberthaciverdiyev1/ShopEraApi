import { writable } from 'svelte/store';

/** Search overlay visibility, shared between the header trigger and the search area. */
export const searchOpen = writable(false);
