<script lang="ts">
	import { goto } from '$app/navigation';
	import { login } from '$lib/services/auth';
	import { translate } from '$lib/i18n';

	let identifier = $state('');
	let password = $state('');
	let showPassword = $state(false);
	let error = $state<string | null>(null);
	let loading = $state(false);

	async function onSubmit(event: SubmitEvent) {
		event.preventDefault();
		loading = true;
		error = null;

		try {
			await login(identifier, password);
			await goto('/');
		} catch (e) {
			error = e instanceof Error ? e.message : $translate('Login failed');
		} finally {
			loading = false;
		}
	}
</script>

<!-- Login Section start -->
<!-- Login Section start  -->
<section class="login-section fix section-padding">
<div class="container">
<div class="login-wrapper">
<div class="row gx-5">
    <div class="col-xl-6 offset-xl-0 col-md-8 offset-md-2">
        <div class="contact-info-area">
            <div class="contact-content">
                <h2 class="contact-content__title">{$translate('Get Started Now')}</h2>
                <p class="contact-content__subtitle">{$translate('Enter your Credentials to access your account')}</p>
                <form onsubmit={onSubmit} class="contact-form-items">
                    <div class="row g-4">
                        <div class="col-lg-12 fadeInUp">
                            <div class="form-clt">
                                <span>{$translate('Phone or email*')}</span>
                                <input type="text" name="identifier" placeholder={$translate('Phone number or email')}
                                       bind:value={identifier} required>
                            </div>
                        </div>
                        <div class="col-lg-12 fadeInUp">
                            <div class="form-clt">
                                <span>{$translate('Password*')}</span>
                                <div class="pw-field">
                                    <input type={showPassword ? 'text' : 'password'} name="password" placeholder="********"
                                           bind:value={password} required>
                                    <button type="button" class="pw-toggle"
                                            aria-label={showPassword ? $translate('Hide password') : $translate('Show password')}
                                            onclick={() => (showPassword = !showPassword)}>
                                        {#if showPassword}
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
                            </div>
                        </div>
                        {#if error}
                            <div class="col-lg-12">
                                <p class="form-error">{error}</p>
                            </div>
                        {/if}
                        <div class="col-lg-12 fadeInUp">
                            <button type="submit" class="theme-btn style6" disabled={loading}>
                                {loading ? $translate('Signing in…') : $translate('Sign In')}
                            </button>
                        </div>
                    </div>
                </form>
                <h5 class="contact-content__logtitle center">{$translate("Don't Have an account?")} <a href="/register">{$translate('Sign Up')}</a></h5>
             </div>
       </div>
    </div>
   <div class="col-xl-6 offset-xl-0 col-md-8 offset-md-2 d-none d-sm-block">
    <div class="">
        <img src="/assets/images/register/loginThumb.jpg" alt="register-thumb">
    </div>
 </div>
   </div>
</div>
</div>
 </section>

<style>
	.login-section {
		background:
			radial-gradient(circle at 20% 0%, color-mix(in srgb, var(--theme) 10%, transparent), transparent 34%),
			#f7f8fb;
	}

	.login-wrapper :global(.row) {
		align-items: center;
		justify-content: center;
	}

	.login-wrapper {
		max-width: 1120px;
		margin: 0 auto;
		border: 1px solid rgba(15, 23, 42, 0.08);
		border-radius: 24px;
		background: #ffffff;
		box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
		overflow: hidden;
	}

	.contact-info-area {
		padding: 0 !important;
		border: 0 !important;
		border-radius: 0 !important;
		background: transparent !important;
		box-shadow: none !important;
	}

	.contact-content {
		padding: 36px;
	}

	.contact-content__title {
		margin-bottom: 8px;
		color: #0f172a;
		font-size: 34px;
		font-weight: 850;
		line-height: 1.08;
	}

	.contact-content__subtitle {
		margin-bottom: 26px;
		color: #64748b;
		font-size: 15px;
		line-height: 1.45;
	}

	.contact-form-items :global(.row) {
		--bs-gutter-y: 18px;
	}

	.form-clt span {
		display: block;
		margin-bottom: 8px;
		color: #1f2937;
		font-size: 13px;
		font-weight: 800;
		line-height: 1.2;
	}

	.form-clt input {
		width: 100%;
		min-height: 54px;
		padding: 0 16px;
		border: 1.5px solid #d5dde8;
		border-radius: 14px;
		background: #ffffff;
		color: #0f172a;
		font-size: 15px;
		font-weight: 600;
		outline: 0;
		transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
	}

	.form-clt input::placeholder {
		color: #7b8496;
		font-weight: 500;
		opacity: 1;
	}

	.form-clt input:focus {
		border-color: var(--theme);
		background: #fff;
		box-shadow: 0 0 0 4px color-mix(in srgb, var(--theme) 16%, transparent);
	}

	.pw-field {
		position: relative;
	}

	.pw-field input {
		padding-right: 48px;
	}

	.pw-toggle {
		position: absolute;
		top: 50%;
		right: 10px;
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

	.contact-form-items :global(.theme-btn) {
		width: 100%;
		min-height: 56px;
		margin-top: 4px;
		border-radius: 18px !important;
		font-size: 15px !important;
		box-shadow: 0 14px 26px color-mix(in srgb, var(--theme) 24%, transparent);
	}

	.form-error {
		color: #e2453c;
		font-size: 14px;
		margin: 0;
	}

	.contact-content__logtitle {
		margin: 26px 0 0;
		color: #334155;
		font-size: 14px;
		font-weight: 600;
		text-align: center;
	}

	.contact-content__logtitle a {
		color: var(--theme);
		font-weight: 850;
		text-decoration: none;
	}

	.login-thumb {
		border-radius: 24px;
		overflow: hidden;
		box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
	}

	.login-thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	@media (max-width: 767.98px) {
		.login-section {
			padding-top: 30px !important;
		}

		.login-section :global(.container) {
			padding-right: 16px;
			padding-left: 16px;
		}

		.login-wrapper {
			border-radius: 22px;
			box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
		}

		.contact-content {
			padding: 24px 18px;
		}

		.contact-content__title {
			font-size: 28px;
		}

		.contact-content__subtitle {
			margin-bottom: 22px;
			font-size: 14px;
		}

		.form-clt input {
			min-height: 52px;
			border-radius: 13px;
			font-size: 14px;
		}
	}
</style>
