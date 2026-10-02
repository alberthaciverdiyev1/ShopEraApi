<script lang="ts">
	import { goto } from '$app/navigation';
	import { register } from '$lib/services/auth';

	let name = $state('');
	let phone = $state('');
	let email = $state('');
	let password = $state('');
	let error = $state<string | null>(null);
	let loading = $state(false);

	async function onSubmit(event: SubmitEvent) {
		event.preventDefault();
		loading = true;
		error = null;

		try {
			await register({
				name,
				phone,
				email: email.trim() || undefined,
				password
			});
			await goto('/');
		} catch (e) {
			error = e instanceof Error ? e.message : 'Registration failed';
		} finally {
			loading = false;
		}
	}
</script>

<!-- Register Section start -->
<!-- Register Section start  -->
 <section class="register-section fix section-padding">
<div class="container">
<div class="register-wrapper">
<div class="row gx-5">
    <div class="col-xl-6 offset-xl-0 col-md-8 offset-md-2">
        <div class="contact-info-area">
            <div class="contact-content">
                <h2 class="contact-content__title">Get Started Now</h2>
                <p class="contact-content__subtitle">Enter your Credentials to access your account</p>
                <form onsubmit={onSubmit} class="contact-form-items">
                    <div class="row g-4">
                        <div class="col-lg-12 fadeInUp">
                            <div class="form-clt">
                                <span>Your name*</span>
                                <input type="text" name="name" placeholder="Enter your name"
                                       bind:value={name} required>
                            </div>
                        </div>
                        <div class="col-lg-12 fadeInUp">
                            <div class="form-clt">
                                <span>Phone number*</span>
                                <input type="text" name="phone" placeholder="Enter your phone number"
                                       bind:value={phone} required>
                            </div>
                        </div>
                        <div class="col-lg-12 fadeInUp">
                            <div class="form-clt">
                                <span>Email address</span>
                                <input type="email" name="email" placeholder="Enter your email (optional)"
                                       bind:value={email}>
                            </div>
                        </div>
                        <div class="col-lg-12 fadeInUp">
                            <div class="form-clt">
                                <span>Password*</span>
                                <input type="password" name="password" placeholder="********"
                                       bind:value={password} required minlength="6">
                            </div>
                        </div>
                        {#if error}
                            <div class="col-lg-12">
                                <p class="form-error">{error}</p>
                            </div>
                        {/if}
                        <div class="col-lg-12 fadeInUp">
                            <button type="submit" class="theme-btn style6" disabled={loading}>
                                {loading ? 'Creating account…' : 'Sign Up'}
                            </button>
                        </div>
                    </div>
                </form>
                <h5 class="contact-content__logtitle center">Have an account? <a href="/login">Sign In</a></h5>
             </div>
       </div>
    </div>
   <div class="col-xl-6 offset-xl-0 col-md-8 offset-md-2 d-none d-sm-block">
    <div class="register-thumb">
        <img src="/assets/images/register/loginThumb.jpg" alt="register-thumb">
    </div>
 </div>
   </div>
</div>
</div>
 </section>

<style>
	.register-section {
		background:
			radial-gradient(circle at 20% 0%, color-mix(in srgb, var(--theme) 10%, transparent), transparent 34%),
			#f7f8fb;
	}

	.register-wrapper :global(.row) {
		align-items: center;
		justify-content: center;
	}

	.register-wrapper {
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

	.register-thumb {
		border-radius: 24px;
		overflow: hidden;
		box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
	}

	.register-thumb img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	@media (max-width: 767.98px) {
		.register-section {
			padding-top: 30px !important;
		}

		.register-section :global(.container) {
			padding-right: 16px;
			padding-left: 16px;
		}

		.register-wrapper {
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
