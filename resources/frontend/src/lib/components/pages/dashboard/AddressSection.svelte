<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { isLoggedIn } from '$lib/services/auth';
	import Button from '$lib/components/ui/Button.svelte';
	import {
		createAddress,
		deleteAddress,
		fetchAddresses,
		setDefaultAddress,
		updateAddress,
		type ApiAddress,
		type AddressPayload
	} from '$lib/services/account';
	import { loadDeliveryCities, type ApiDeliveryCity } from '$lib/services/delivery';

	let { onchange }: { onchange?: () => void } = $props();

	const emptyAddress: AddressPayload = {
		full_name: '',
		contact_number: '',
		city: '',
		town_village_district: '',
		street_building_number: '',
		unit_floor_apartment: '',
		is_default: false
	};

	let addresses = $state<ApiAddress[]>([]);
	let deliveryCities = $state<ApiDeliveryCity[]>([]);
	// Cities come from the API (/city); no hardcoded fallback list.
	const availableCities = $derived<string[]>(deliveryCities.map((c) => c.name || c.key));

	let loading = $state(true);
	let form = $state<AddressPayload>({ ...emptyAddress });
	let editingId = $state<number | null>(null);
	let showForm = $state(false);
	let saving = $state(false);
	let settingDefaultId = $state<number | null>(null);
	let deletingId = $state<number | null>(null);
	let successMessage = $state<string | null>(null);
	let errorMessage = $state<string | null>(null);

	onMount(async () => {
		if ($isLoggedIn) {
			loadAddresses();
			deliveryCities = await loadDeliveryCities();
		} else {
			loading = false;
		}
	});

	async function loadAddresses() {
		loading = true;
		try {
			addresses = await fetchAddresses();
			onchange?.();
		} catch (e) {
			addresses = [];
			errorMessage = e instanceof Error ? e.message : 'Could not load addresses.';
		} finally {
			loading = false;
		}
	}

	function startAdd() {
		editingId = null;
		form = {
			...emptyAddress,
			city: availableCities[0] ?? '',
			is_default: addresses.length === 0
		};
		showForm = true;
		successMessage = null;
		errorMessage = null;
	}

	function startEdit(address: ApiAddress) {
		editingId = address.id;
		form = {
			full_name: address.full_name ?? '',
			contact_number: String(address.contact_number ?? ''),
			city: address.city ?? availableCities[0] ?? '',
			town_village_district: address.town_village_district ?? '',
			street_building_number: address.street_building_number ?? '',
			unit_floor_apartment: address.unit_floor_apartment ?? '',
			is_default: !!address.is_default
		};
		showForm = true;
		successMessage = null;
		errorMessage = null;
	}

	function cancelForm() {
		editingId = null;
		form = { ...emptyAddress };
		showForm = false;
		errorMessage = null;
	}

	async function saveAddress(event: SubmitEvent) {
		event.preventDefault();
		saving = true;
		successMessage = null;
		errorMessage = null;

		try {
			if (editingId) {
				await updateAddress(editingId, form);
				successMessage = 'Address updated successfully.';
			} else {
				await createAddress(form);
				successMessage = 'Address added successfully.';
			}
			cancelForm();
			await loadAddresses();
		} catch (e) {
			errorMessage = e instanceof Error ? e.message : 'Could not save the address.';
		} finally {
			saving = false;
		}
	}

	async function handleSetDefault(id: number) {
		settingDefaultId = id;
		successMessage = null;
		errorMessage = null;

		try {
			await setDefaultAddress(id);
			successMessage = 'Default address updated successfully.';
			await loadAddresses();
		} catch (e) {
			errorMessage = e instanceof Error ? e.message : 'Could not set default address.';
		} finally {
			settingDefaultId = null;
		}
	}

	async function handleDelete(id: number) {
		if (!confirm('Are you sure you want to delete this address?')) {
			return;
		}

		deletingId = id;
		successMessage = null;
		errorMessage = null;

		try {
			await deleteAddress(id);
			if (editingId === id) {
				cancelForm();
			}
			successMessage = 'Address deleted successfully.';
			await loadAddresses();
		} catch (e) {
			errorMessage = e instanceof Error ? e.message : 'Could not delete the address.';
		} finally {
			deletingId = null;
		}
	}
</script>

<div class="address-section-container">
	<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
		<div>
			<h3 class="address-heading mb-1">
				<i class="fa-solid fa-location-dot me-2 text-danger"></i>My Addresses
			</h3>
			<p class="text-muted mb-0">{$translate('Manage your delivery addresses.')}</p>
		</div>

		{#if !showForm}
			<button type="button" class="theme-btn btn-sm d-inline-flex align-items-center gap-2" onclick={startAdd}>
				<i class="fa-solid fa-plus"></i> Add New Address
			</button>
		{/if}
	</div>

	{#if successMessage}
		<div class="alert alert-success d-flex align-items-center justify-content-between mb-4 fade show" role="alert">
			<div class="d-flex align-items-center gap-2">
				<i class="fa-solid fa-circle-check fs-5"></i>
				<span>{successMessage}</span>
			</div>
			<button type="button" class="btn-close" aria-label="Close" onclick={() => (successMessage = null)}></button>
		</div>
	{/if}

	{#if errorMessage}
		<div class="alert alert-danger d-flex align-items-center justify-content-between mb-4 fade show" role="alert">
			<div class="d-flex align-items-center gap-2">
				<i class="fa-solid fa-circle-exclamation fs-5"></i>
				<span>{errorMessage}</span>
			</div>
			<button type="button" class="btn-close" aria-label="Close" onclick={() => (errorMessage = null)}></button>
		</div>
	{/if}

	{#if showForm}
		<div class="delivery-address-box mb-4">
			<h4 class="section-title d-flex justify-content-between align-items-center">
				<span>{editingId ? 'Edit Address' : 'Add New Address'}</span>
				<button type="button" class="btn btn-sm btn-link text-muted p-0" onclick={cancelForm}>
					<i class="fa-solid fa-xmark fs-5"></i>
				</button>
			</h4>
			<div class="form-wrapper">
				<form onsubmit={saveAddress}>
					<div class="row g-3">
						<div class="col-md-6">
							<label for="address_full_name" class="form-label">{$translate('Full Name')} <span class="text-danger">*</span></label>
							<input
								type="text"
								id="address_full_name"
								class="form-control"
								placeholder="e.g. John Doe"
								bind:value={form.full_name}
								required
							/>
						</div>

						<div class="col-md-6">
							<label for="address_phone" class="form-label">{$translate('Contact Number')} <span class="text-danger">*</span></label>
							<input
								type="tel"
								id="address_phone"
								class="form-control"
								placeholder="e.g. 0501234567"
								bind:value={form.contact_number}
								required
							/>
						</div>

						<div class="col-md-6">
							<label for="address_city" class="form-label">{$translate('City')} <span class="text-danger">*</span></label>
							<select
								id="address_city"
								class="form-select"
								bind:value={form.city}
								required
							>
								{#each availableCities as city (city)}
									<option value={city}>{city}</option>
								{/each}
							</select>
						</div>

						<div class="col-md-6">
							<label for="address_district" class="form-label">{$translate('District / Town')} <span class="text-danger">*</span></label>
							<input
								type="text"
								id="address_district"
								class="form-control"
								placeholder="e.g. Yasamal"
								bind:value={form.town_village_district}
								required
							/>
						</div>

						<div class="col-md-8">
							<label for="address_street" class="form-label">{$translate('Street & Building')} <span class="text-danger">*</span></label>
							<input
								type="text"
								id="address_street"
								class="form-control"
								placeholder="e.g. Nizami str. 42"
								bind:value={form.street_building_number}
								required
							/>
						</div>

						<div class="col-md-4">
							<label for="address_unit" class="form-label">{$translate('Apt / Floor / Unit')} <span class="text-danger">*</span></label>
							<input
								type="text"
								id="address_unit"
								class="form-control"
								placeholder="e.g. Apt 12, Floor 3"
								bind:value={form.unit_floor_apartment}
								required
							/>
						</div>

						<div class="col-12 mt-2">
							<div class="form-check">
								<input
									type="checkbox"
									class="form-check-input"
									id="address_is_default"
									bind:checked={form.is_default}
								/>
								<label class="form-check-label" for="address_is_default">
									Set as default delivery address
								</label>
							</div>
						</div>
					</div>

					<div class="d-flex gap-2 mt-4">
						<button type="submit" class="theme-btn" disabled={saving}>
							{saving ? 'Saving…' : editingId ? 'Update Address' : 'Save Address'}
						</button>
						<button type="button" class="btn btn-outline-secondary px-4" onclick={cancelForm} disabled={saving}>
							Cancel
						</button>
					</div>
				</form>
			</div>
		</div>
	{/if}

	{#if loading}
		<div class="text-center py-5">
			<div class="spinner-border text-danger" role="status">
				<span class="visually-hidden">{$translate('Loading addresses...')}</span>
			</div>
			<p class="text-muted mt-2">{$translate('Loading addresses...')}</p>
		</div>
	{:else if addresses.length === 0 && !showForm}
		<div class="empty-address-card text-center py-5">
			<div class="empty-icon mb-3">
				<i class="fa-light fa-map-location-dot"></i>
			</div>
			<h4>{$translate('No Addresses Saved')}</h4>
			<p class="text-muted mb-3">{$translate('You have not added any delivery addresses yet.')}</p>
			<button type="button" class="theme-btn" onclick={startAdd}>
				<i class="fa-solid fa-plus me-1"></i> Add Your First Address
			</button>
		</div>
	{:else}
		<div class="row g-3">
			{#each addresses as address (address.id)}
				<div class="col-md-6">
					<div class="address-item-card h-100" class:is-default-card={address.is_default}>
						<div class="card-header-row d-flex justify-content-between align-items-center mb-2">
							<h5 class="address-name mb-0 text-truncate">{address.full_name}</h5>
							{#if address.is_default}
								<span class="default-badge">
									<i class="fa-solid fa-circle-check me-1"></i> Default
								</span>
							{/if}
						</div>

						<div class="address-details mb-3">
							<p class="detail-line mb-1">
								<i class="fa-solid fa-location-dot me-2 text-muted"></i>
								<span>
									{address.street_building_number}{#if address.unit_floor_apartment}, {address.unit_floor_apartment}{/if},
									{address.town_village_district}, {address.city}
								</span>
							</p>
							{#if address.contact_number}
								<p class="detail-line mb-0">
									<i class="fa-solid fa-phone me-2 text-muted"></i>
									<span>{address.contact_number}</span>
								</p>
							{/if}
						</div>

						<div class="card-actions-row d-flex align-items-center justify-content-between pt-3 border-top mt-auto flex-wrap gap-2">
							<div class="default-action">
								{#if !address.is_default}
									<button
										type="button"
										class="btn btn-sm btn-outline-primary default-btn"
										onclick={() => handleSetDefault(address.id)}
										disabled={settingDefaultId === address.id}
									>
										{#if settingDefaultId === address.id}
											<span class="spinner-border spinner-border-sm me-1" role="status"></span>
											Setting…
										{:else}
											<i class="fa-regular fa-star me-1"></i> Set as Default
										{/if}
									</button>
								{/if}
							</div>

							<div class="action-buttons d-flex gap-2">
								<button
									type="button"
									class="btn btn-sm btn-outline-secondary"
									onclick={() => startEdit(address)}
									title="Edit Address"
								>
									<i class="fa-regular fa-pen-to-square me-1"></i> Edit
								</button>
								<button
									type="button"
									class="btn btn-sm btn-outline-danger"
									onclick={() => handleDelete(address.id)}
									disabled={deletingId === address.id}
									title="Delete Address"
								>
									{#if deletingId === address.id}
										<span class="spinner-border spinner-border-sm" role="status"></span>
									{:else}
										<i class="fa-regular fa-trash-can me-1"></i> Delete
									{/if}
								</button>
							</div>
						</div>
					</div>
				</div>
			{/each}
		</div>
	{/if}
</div>

<style>
	.address-section-container {
		width: 100%;
	}

	.address-heading {
		font-size: 22px;
		font-weight: 600;
		color: #0a111e;
	}

	.delivery-address-box {
		border-radius: 8px;
		border: 1px solid #e6e6e6;
		background: #fff;
		overflow: hidden;
	}

	.delivery-address-box .section-title {
		padding: 16px 24px;
		color: #0a111e;
		font-size: 18px;
		font-weight: 600;
		border-bottom: 1px solid #e5e5e5;
		margin: 0;
		background-color: #fff;
	}

	.delivery-address-box .form-wrapper {
		padding: 24px;
	}

	.form-label {
		font-size: 14px;
		font-weight: 500;
		color: #333;
		margin-bottom: 6px;
	}

	.form-control,
	.form-select {
		padding: 10px 14px;
		border: 1px solid #d9d9d9;
		border-radius: 6px;
		font-size: 14px;
		transition: border-color 0.2s;
	}

	.form-control:focus,
	.form-select:focus {
		border-color: #ed0006;
		box-shadow: 0 0 0 0.2rem rgba(237, 0, 6, 0.15);
	}

	.form-check-input:checked {
		background-color: #ed0006;
		border-color: #ed0006;
	}

	.empty-address-card {
		background: #fff;
		border: 1px dashed #d0d5dd;
		border-radius: 10px;
		padding: 48px 24px;
	}

	.empty-icon {
		font-size: 48px;
		color: #98a2b3;
	}

	.address-item-card {
		background: #fff;
		border: 1px solid #e6e6e6;
		border-radius: 8px;
		padding: 20px;
		display: flex;
		flex-direction: column;
		transition: border-color 0.2s, box-shadow 0.2s;
	}

	.address-item-card:hover {
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
	}

	.address-item-card.is-default-card {
		border: 2px solid #16a34a;
		background: #fbfdfc;
	}

	.address-name {
		font-size: 16px;
		font-weight: 600;
		color: #0a111e;
	}

	.default-badge {
		font-size: 12px;
		font-weight: 600;
		color: #16a34a;
		background: #dcfce7;
		padding: 3px 10px;
		border-radius: 20px;
		display: inline-flex;
		align-items: center;
	}

	.detail-line {
		font-size: 14px;
		color: #475467;
		line-height: 1.5;
	}

	.default-btn {
		font-size: 12px;
		padding: 5px 10px;
		border-radius: 6px;
	}
</style>
