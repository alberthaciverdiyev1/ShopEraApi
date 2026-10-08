<script lang="ts">
	let {
		id = '',
		name = '',
		placeholder = 'Password',
		class: className = '',
		value = $bindable('')
	}: {
		id?: string;
		name?: string;
		placeholder?: string;
		class?: string;
		value?: string;
	} = $props();

	let visible = $state(false);
</script>

<div class="pw-field">
	<input
		type={visible ? 'text' : 'password'}
		class="form-control {className}"
		{id}
		{name}
		{placeholder}
		bind:value
	/>
	<button
		type="button"
		class="pw-toggle"
		aria-label={visible ? 'Hide password' : 'Show password'}
		onclick={() => (visible = !visible)}
	>
		{#if visible}
			<svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
				<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
			</svg>
		{:else}
			<svg fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
				<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
				<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
			</svg>
		{/if}
	</button>
</div>

<style>
	.pw-field {
		position: relative;
	}

	.pw-field :global(.form-control) {
		padding-right: 44px;
	}

	.pw-toggle {
		position: absolute;
		top: 50%;
		right: 8px;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		padding: 0;
		border: 0;
		border-radius: 8px;
		background: transparent;
		color: #7b8496;
		cursor: pointer;
		transform: translateY(-50%);
		transition: color 0.15s ease, background-color 0.15s ease;
	}

	.pw-toggle:hover {
		color: #0f172a;
		background: rgba(15, 23, 42, 0.05);
	}

	.pw-toggle svg {
		width: 20px;
		height: 20px;
	}
</style>
