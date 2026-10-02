<script lang="ts">
	import { onMount } from 'svelte';
	import Button from '$lib/components/ui/Button.svelte';
	import { isLoggedIn, user } from '$lib/services/auth';
	import { updateProfile, uploadAvatar, removeAvatar } from '$lib/services/account';

	let { onnavigatetoaddresses }: { onnavigatetoaddresses?: () => void } = $props();

	let profile = $state({ name: '', surname: '', email: '', phone: '' });
	let profileMessage = $state<string | null>(null);
	let profileError = $state<string | null>(null);
	let savingProfile = $state(false);

	let avatarLoading = $state(false);
	let avatarMsg = $state<string | null>(null);
	let avatarErr = $state<string | null>(null);
	const avatarInitials = $derived.by(() => {
		const name = `${$user?.name ?? ''} ${$user?.surname ?? ''}`.trim();
		if (!name) return 'U';
		return name
			.split(/\s+/)
			.slice(0, 2)
			.map((part) => part[0]?.toUpperCase())
			.join('');
	});

	onMount(() => {
		if ($user) {
			profile = {
				name: $user.name ?? '',
				surname: $user.surname ?? '',
				email: $user.email ?? '',
				phone: $user.phone ?? ''
			};
		}
	});

	async function onAvatarSelected(event: Event) {
		const input = event.target as HTMLInputElement;
		const file = input.files?.[0];
		if (!file) return;

		if (file.size > 5 * 1024 * 1024) {
			avatarErr = 'Image size must not exceed 5MB.';
			input.value = '';
			return;
		}

		const valid = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
		if (!valid.includes(file.type)) {
			avatarErr = 'Only JPG, PNG, and WEBP formats are allowed.';
			input.value = '';
			return;
		}

		try {
			avatarLoading = true;
			avatarMsg = null;
			avatarErr = null;
			await uploadAvatar(file);
			avatarMsg = 'Avatar updated successfully.';
		} catch (err) {
			avatarErr = err instanceof Error ? err.message : 'Failed to upload avatar.';
		} finally {
			avatarLoading = false;
			input.value = '';
		}
	}

	async function onAvatarRemove() {
		try {
			avatarLoading = true;
			avatarMsg = null;
			avatarErr = null;
			await removeAvatar();
			avatarMsg = 'Avatar removed successfully.';
		} catch (err) {
			avatarErr = err instanceof Error ? err.message : 'Failed to remove avatar.';
		} finally {
			avatarLoading = false;
		}
	}

	async function saveProfile(event: SubmitEvent) {
		event.preventDefault();
		savingProfile = true;
		profileMessage = profileError = null;

		try {
			await updateProfile({
				name: profile.name || undefined,
				surname: profile.surname || undefined,
				email: profile.email || undefined,
				phone: profile.phone || undefined
			});
			profileMessage = 'Profile updated successfully.';
		} catch (e) {
			profileError = e instanceof Error ? e.message : 'Could not update profile';
		} finally {
			savingProfile = false;
		}
	}
</script>

<div class="account-settings">
	<h4 class="section-title">Account Settings</h4>
	<div class="form-wrapper">
		{#if !$isLoggedIn}
			<div class="text-center py-5">
				<p>Please sign in to manage your account settings.</p>
				<Button href="/login">Login</Button>
			</div>
		{:else}
			<!-- Avatar Management Section -->
			<div class="avatar-section d-flex align-items-center flex-wrap gap-4 pb-4 mb-4 border-bottom">
				<div class="avatar-preview-wrapper position-relative">
					{#if $user?.avatar}
						<img src={$user.avatar} alt="Avatar Preview" class="account-avatar-img" />
					{:else}
						<div class="account-avatar-placeholder" aria-label="Avatar placeholder">
							<span>{avatarInitials}</span>
						</div>
					{/if}
					{#if avatarLoading}
						<div class="account-avatar-loading">
							<i class="fa-solid fa-spinner fa-spin"></i>
						</div>
					{/if}
				</div>
				<div class="avatar-actions">
					<h6 class="mb-1">Profile Picture</h6>
					<p class="text-muted small mb-3">JPG, PNG or WEBP. Max size 5MB.</p>
					<div class="d-flex align-items-center gap-2 flex-wrap">
						<label class="btn btn-outline-danger btn-sm m-0" class:disabled={avatarLoading}>
							<i class="fa-solid fa-cloud-arrow-up me-1"></i>
							{avatarLoading ? 'Uploading…' : 'Upload New'}
							<input
								type="file"
								accept="image/jpeg,image/png,image/jpg,image/webp"
								class="d-none"
								onchange={onAvatarSelected}
								disabled={avatarLoading}
							/>
						</label>
						{#if $user?.avatar}
							<button
								type="button"
								class="btn btn-outline-secondary btn-sm"
								disabled={avatarLoading}
								onclick={onAvatarRemove}
							>
								<i class="fa-solid fa-trash-can me-1"></i> Remove
							</button>
						{/if}
					</div>
					{#if avatarMsg}
						<div class="text-success small mt-2">
							<i class="fa-solid fa-circle-check me-1"></i>{avatarMsg}
						</div>
					{/if}
					{#if avatarErr}
						<div class="text-danger small mt-2">
							<i class="fa-solid fa-circle-exclamation me-1"></i>{avatarErr}
						</div>
					{/if}
				</div>
			</div>

			<form onsubmit={saveProfile}>
				<div class="row g-3">
					<div class="col-md-6">
						<label for="profile_name" class="form-label">Name</label>
						<input type="text" id="profile_name" class="form-control" bind:value={profile.name} required />
					</div>
					<div class="col-md-6">
						<label for="profile_surname" class="form-label">Surname</label>
						<input type="text" id="profile_surname" class="form-control" bind:value={profile.surname} />
					</div>
					<div class="col-md-6">
						<label for="profile_email" class="form-label">Email</label>
						<input type="email" id="profile_email" class="form-control" bind:value={profile.email} />
					</div>
					<div class="col-md-6">
						<label for="profile_phone" class="form-label">Phone</label>
						<input type="text" id="profile_phone" class="form-control" bind:value={profile.phone} />
					</div>
				</div>

				{#if profileMessage}
					<div class="alert alert-success mt-3 mb-0" role="alert">
						<i class="fa-solid fa-circle-check me-2"></i>{profileMessage}
					</div>
				{/if}
				{#if profileError}
					<div class="alert alert-danger mt-3 mb-0" role="alert">
						<i class="fa-solid fa-circle-exclamation me-2"></i>{profileError}
					</div>
				{/if}

				<div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
					<button type="submit" class="theme-btn" disabled={savingProfile}>
						{savingProfile ? 'Saving…' : 'Save Changes'}
					</button>

					{#if onnavigatetoaddresses}
						<button type="button" class="btn btn-link text-decoration-none" onclick={onnavigatetoaddresses}>
							<i class="fa-solid fa-location-dot me-1"></i> Manage delivery addresses &rarr;
						</button>
					{/if}
				</div>
			</form>
		{/if}
	</div>
</div>

<style>
	.account-settings {
		border-radius: 8px;
		border: 1px solid #e6e6e6;
		background: #fff;
		overflow: hidden;
	}

	.section-title {
		padding: 16px 24px;
		color: #0a111e;
		font-size: 18px;
		font-weight: 600;
		border-bottom: 1px solid #e5e5e5;
		margin: 0;
		background-color: #fff;
	}

	.form-wrapper {
		padding: 24px;
	}

	.avatar-preview-wrapper {
		width: 90px;
		height: 90px;
		flex-shrink: 0;
	}

	.account-avatar-img {
		width: 90px;
		height: 90px;
		border-radius: 50%;
		object-fit: cover;
		border: 2px solid #e6e6e6;
		display: block;
	}

	.account-avatar-placeholder {
		position: relative;
		display: flex;
		align-items: center;
		justify-content: center;
		width: 90px;
		height: 90px;
		border: 2px solid color-mix(in srgb, var(--theme) 38%, #dbe3ef);
		border-radius: 50%;
		background:
			radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.92), transparent 34%),
			linear-gradient(135deg, color-mix(in srgb, var(--theme) 18%, #f8fafc), #eef4ff);
		box-shadow: 0 14px 30px rgba(15, 23, 42, 0.1);
		color: color-mix(in srgb, var(--theme) 72%, #17213b);
		overflow: hidden;
	}

	.account-avatar-placeholder::after {
		content: '';
		position: absolute;
		right: 12px;
		bottom: 10px;
		width: 14px;
		height: 14px;
		border: 3px solid #ffffff;
		border-radius: 50%;
		background: var(--theme);
	}

	.account-avatar-placeholder span {
		font-size: 28px;
		font-weight: 900;
		letter-spacing: 0;
		line-height: 1;
	}

	.account-avatar-loading {
		position: absolute;
		top: 0;
		left: 0;
		width: 90px;
		height: 90px;
		border-radius: 50%;
		background: rgba(0, 0, 0, 0.45);
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 20px;
	}

	.form-label {
		font-size: 14px;
		font-weight: 500;
		color: #333;
		margin-bottom: 6px;
	}

	.form-control {
		padding: 10px 14px;
		border: 1px solid #d9d9d9;
		border-radius: 6px;
		font-size: 14px;
	}

	.form-control:focus {
		border-color: #ed0006;
		box-shadow: 0 0 0 0.2rem rgba(237, 0, 6, 0.15);
	}
</style>
