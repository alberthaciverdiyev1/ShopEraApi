<script lang="ts">
	import { onMount } from 'svelte';
	import { isLoggedIn, user } from '$lib/services/auth';
	import { basketItems, basketLoading, basketTotal, loadBasket } from '$lib/services/basket';
	import { appliedPromo, applyPromoCode, clearPromo } from '$lib/services/promo';
	import {
		CITIES,
		fetchAddresses,
		createAddress,
		type ApiAddress,
		type AddressPayload
	} from '$lib/services/account';
	import {
		fetchOrderPreview,
		createOrderFromBasket,
		type OrderPreview,
		type CreateOrderPayload
	} from '$lib/services/orders';
	import {
		loadPickupPoints,
		loadDeliveryCities,
		loadCityDeliveryDetails,
		getLocalizedDeliveryTime,
		type ApiPickupPoint,
		type ApiDeliveryCity,
		type ApiDeliveryDetail
	} from '$lib/services/delivery';
	import { locale, translate } from '$lib/i18n';
	import { apiGet } from '$lib/utils/api';
	import { fetchFeatures, features, type ApiFeatures } from '$lib/services/features';

	let featureSet = $state<ApiFeatures | null>(null);
	const canCard = $derived(featureSet ? featureSet.online_payment !== false : true);
	const canCash = $derived(featureSet ? featureSet.cash_on_delivery !== false : true);
	const canWhatsapp = $derived(featureSet ? featureSet.whatsapp_orders !== false : true);

	onMount(async () => {
		featureSet = await fetchFeatures();
		if (paymentType === 'CARD' && !canCard) {
			paymentType = canCash ? 'CASH' : 'WHATSAPP';
		} else if (paymentType === 'CASH' && !canCash) {
			paymentType = canCard ? 'CARD' : 'WHATSAPP';
		} else if (paymentType === 'WHATSAPP' && !canWhatsapp) {
			paymentType = canCard ? 'CARD' : 'CASH';
		}
	});

	let waBusy = $state(false);
	let waError = $state('');

	async function orderViaWhatsapp() {
		waError = '';
		waBusy = true;
		try {
			const res = await apiGet<{ url: string | null }>('/order/whatsapp-link');
			if (res?.url) {
				window.open(res.url, '_blank');
			} else {
				waError = 'WhatsApp nömrəsi təyin olunmayıb.';
			}
		} catch (e) {
			waError = (e as Error)?.message || 'Alınmadı.';
		} finally {
			waBusy = false;
		}
	}

	// Delivery Method State
	let deliveryMethod = $state<'COURIER' | 'PICKUP_POINT' | 'TAKE_FROM_STORE'>('COURIER');
	let courierSpeed = $state<'STANDARD' | 'STANDARD_FAST'>('STANDARD');

	// Pickup Points State
	let pickupPointsList = $state<ApiPickupPoint[]>([]);
	let pickupPointsLoading = $state(false);
	let selectedPickupPointId = $state<number | null>(null);

	// Delivery Cities & Rates State
	let deliveryCitiesList = $state<ApiDeliveryCity[]>([]);
	let cityRates = $state<ApiDeliveryDetail | null>(null);
	let cityRatesLoading = $state(false);

	// Addresses State
	let savedAddresses = $state<ApiAddress[]>([]);
	let selectedAddressId = $state<number | null>(null);
	let addressLoading = $state(true);
	let savingAddress = $state(false);

	let activeAddressForm = $state<AddressPayload>({
		full_name: '',
		contact_number: '',
		city: CITIES[0],
		town_village_district: '',
		street_building_number: '',
		unit_floor_apartment: '',
		location_label: '',
		is_default: false
	});

	// Order Preview & Placement State
	let preview = $state<OrderPreview | null>(null);
	let previewLoading = $state(false);
	let previewError = $state<string | null>(null);

	let paymentType = $state<'CARD' | 'CASH' | 'WHATSAPP'>('CARD');
	let orderNotes = $state('');

	let promoCodeInput = $state('');
	let promoLoading = $state(false);
	let promoError = $state<string | null>(null);
	let promoSuccess = $state<string | null>(null);

	let submitting = $state(false);
	let submitError = $state<string | null>(null);
	let orderPlacedSuccess = $state(false);
	let placedTransactionId = $state<string | null>(null);

	// Derived calculations
	const effectiveAddressType = $derived<'STANDARD' | 'STANDARD_FAST' | 'PICKUP_POINT' | 'TAKE_FROM_STORE'>(
		deliveryMethod === 'PICKUP_POINT'
			? 'PICKUP_POINT'
			: deliveryMethod === 'TAKE_FROM_STORE'
			? 'TAKE_FROM_STORE'
			: courierSpeed
	);

	const effectiveAddressTypeId = $derived<number | null>(
		deliveryMethod === 'PICKUP_POINT'
			? selectedPickupPointId
			: deliveryMethod === 'TAKE_FROM_STORE'
			? null
			: selectedAddressId
	);

	const availableCityNames = $derived<string[]>(
		deliveryCitiesList.length > 0
			? deliveryCitiesList.map((c) => c.name || c.key)
			: CITIES
	);

	const activePickupPoint = $derived<ApiPickupPoint | null>(
		pickupPointsList.find((p) => p.id === selectedPickupPointId) ?? null
	);

	const isReadyToPlace = $derived(
		$basketItems.length > 0 &&
		(
			(deliveryMethod === 'COURIER' && !!selectedAddressId) ||
			(deliveryMethod === 'PICKUP_POINT' && !!selectedPickupPointId) ||
			(deliveryMethod === 'TAKE_FROM_STORE')
		)
	);

	function profilePhone() {
		return $user?.phone ? String($user.phone) : '';
	}

	onMount(async () => {
		if ($isLoggedIn) {
			await Promise.all([
				loadBasket(),
				loadUserAddresses(),
				initDeliveryOptions()
			]);
		} else {
			addressLoading = false;
		}
	});

	async function initDeliveryOptions() {
		pickupPointsLoading = true;
		try {
			const [points, cities] = await Promise.all([
				loadPickupPoints(),
				loadDeliveryCities()
			]);
			pickupPointsList = points;
			deliveryCitiesList = cities;

			if (points.length > 0 && !selectedPickupPointId) {
				selectedPickupPointId = points[0].id;
			}
		} finally {
			pickupPointsLoading = false;
		}
	}

	async function updateCityRates(cityName: string) {
		cityRatesLoading = true;
		try {
			cityRates = await loadCityDeliveryDetails(cityName);
		} catch {
			cityRates = null;
		} finally {
			cityRatesLoading = false;
		}
	}

	async function loadUserAddresses() {
		addressLoading = true;
		try {
			const list = await fetchAddresses();
			savedAddresses = list;

			if (list.length > 0) {
				const defaultAddr = list.find((a) => a.is_default) || list[0];
				selectAddress(defaultAddr);
			} else {
				selectedAddressId = null;
				activeAddressForm = {
					full_name: '',
					contact_number: profilePhone(),
					city: availableCityNames[0] || CITIES[0],
					town_village_district: '',
					street_building_number: '',
					unit_floor_apartment: '',
					location_label: '',
					is_default: true
				};
				updateCityRates(activeAddressForm.city || 'Baku');
			}
		} catch {
			savedAddresses = [];
		} finally {
			addressLoading = false;
		}
	}

	function selectAddress(addr: ApiAddress) {
		selectedAddressId = addr.id;
		activeAddressForm = {
			full_name: addr.full_name ?? '',
			contact_number: addr.contact_number ? String(addr.contact_number) : '',
			city: addr.city ?? availableCityNames[0] ?? CITIES[0],
			town_village_district: addr.town_village_district ?? '',
			street_building_number: addr.street_building_number ?? '',
			unit_floor_apartment: addr.unit_floor_apartment ?? '',
			location_label: addr.location_label ?? '',
			is_default: !!addr.is_default
		};
		if (addr.city) {
			updateCityRates(addr.city);
		}
		loadPreview();
	}

	function handleAddressSelect(event: Event) {
		const value = Number((event.currentTarget as HTMLSelectElement).value);
		const address = savedAddresses.find((addr) => addr.id === value);
		if (address) {
			selectAddress(address);
		}
	}

	function addressOptionLabel(addr: ApiAddress) {
		const label = addr.location_label || addr.full_name || 'Delivery Address';
		const location = [addr.street_building_number, addr.town_village_district, addr.city]
			.filter(Boolean)
			.join(', ');
		return location ? `${label} - ${location}` : label;
	}

	function startNewAddress() {
		selectedAddressId = null;
		activeAddressForm = {
			full_name: '',
			contact_number: profilePhone(),
			city: availableCityNames[0] || CITIES[0],
			town_village_district: '',
			street_building_number: '',
			unit_floor_apartment: '',
			location_label: '',
			is_default: savedAddresses.length === 0
		};
		updateCityRates(activeAddressForm.city || 'Baku');
		preview = null;
		previewError = null;
	}

	async function saveAndUseAddress(e: SubmitEvent) {
		e.preventDefault();
		savingAddress = true;
		submitError = null;

		try {
			await createAddress(activeAddressForm);
			const list = await fetchAddresses();
			savedAddresses = list;
			const created = list[list.length - 1] || list.find((a) => a.is_default) || list[0];
			if (created) {
				selectAddress(created);
			}
		} catch (err) {
			submitError = err instanceof Error ? err.message : 'Could not save address.';
		} finally {
			savingAddress = false;
		}
	}

	async function loadPreview(promoCode?: string) {
		if (deliveryMethod === 'COURIER' && !selectedAddressId) return;
		if (deliveryMethod === 'PICKUP_POINT' && !selectedPickupPointId) return;

		previewLoading = true;
		previewError = null;
		try {
			const code = promoCode !== undefined ? promoCode : ($appliedPromo?.code ?? '');
			const res = await fetchOrderPreview({
				addressType: effectiveAddressType,
				addressTypeId: effectiveAddressTypeId,
				addressId: deliveryMethod === 'COURIER' ? selectedAddressId : undefined,
				promoCode: code
			});
			preview = res;
		} catch (err) {
			previewError = err instanceof Error ? err.message : 'Could not calculate order preview.';
		} finally {
			previewLoading = false;
		}
	}

	function setDeliveryMethod(method: 'COURIER' | 'PICKUP_POINT' | 'TAKE_FROM_STORE') {
		deliveryMethod = method;
		submitError = null;
		loadPreview();
	}

	function setCourierSpeed(speed: 'STANDARD' | 'STANDARD_FAST') {
		courierSpeed = speed;
		submitError = null;
		loadPreview();
	}

	function selectPickupPoint(id: number) {
		selectedPickupPointId = id;
		submitError = null;
		loadPreview();
	}

	async function handleApplyPromo(e: SubmitEvent) {
		e.preventDefault();
		promoError = null;
		promoSuccess = null;
		const code = promoCodeInput.trim();
		if (!code) return;

		promoLoading = true;
		try {
			const result = await applyPromoCode(code);
			promoSuccess = `Promo code "${result.code}" applied!`;
			promoCodeInput = '';
			await loadPreview(result.code);
		} catch (err) {
			promoError = err instanceof Error ? err.message : 'Invalid promo code.';
		} finally {
			promoLoading = false;
		}
	}

	async function handleRemovePromo() {
		clearPromo();
		promoSuccess = null;
		promoError = null;
		await loadPreview('');
	}

	async function handlePlaceOrder(e?: Event) {
		e?.preventDefault();
		submitError = null;

		if (deliveryMethod === 'COURIER' && !selectedAddressId) {
			submitError = $translate('Please select or save a delivery address first.');
			return;
		}

		if (deliveryMethod === 'PICKUP_POINT' && !selectedPickupPointId) {
			submitError = $translate('Please select a pickup point.');
			return;
		}

		if ($basketItems.length === 0) {
			submitError = 'Your cart is empty.';
			return;
		}

		submitting = true;
		try {
			const payload: CreateOrderPayload = {
				address_type: effectiveAddressType,
				address_type_id: effectiveAddressTypeId,
				payment_type: paymentType,
				pay_with_balance: false,
				promo_code: $appliedPromo?.code || undefined,
				note: orderNotes.trim() || undefined
			};

			const response = await createOrderFromBasket(payload);

			if (response.payment_url) {
				window.location.href = response.payment_url;
				return;
			}

			orderPlacedSuccess = true;
			placedTransactionId = response.transaction_id || null;
			clearPromo();
			await loadBasket();
		} catch (err) {
			submitError = err instanceof Error ? err.message : 'Failed to place order. Please try again.';
		} finally {
			submitting = false;
		}
	}

	function getProductTitle(item: any): string {
		if (item.title) {
			if (typeof item.title === 'string') return item.title;
			if (typeof item.title === 'object') {
				return item.title.az || item.title.en || Object.values(item.title)[0] || 'Product';
			}
		}
		const prod = item.product;
		if (!prod) return 'Product';
		if (typeof prod.title === 'string') return prod.title;
		if (typeof prod.title === 'object' && prod.title !== null) {
			return prod.title.az || prod.title.en || Object.values(prod.title)[0] || 'Product';
		}
		return prod.name || 'Product';
	}

	function getProductImage(item: any): string {
		const prod = item.product;
		if (!prod) return '/assets/images/cart/cart-thumb1_1.jpg';
		if (prod.image) return prod.image;
		if (Array.isArray(prod.images) && prod.images.length > 0) {
			const primary = prod.images.find((img: any) => img.is_primary || img.image_path) || prod.images[0];
			return (
				primary.image_path ||
				primary.url ||
				primary.image ||
				'/assets/images/cart/cart-thumb1_1.jpg'
			);
		}
		return '/assets/images/cart/cart-thumb1_1.jpg';
	}
</script>

<div class="checkout-wrapper section-padding fix">
	<div class="container">
		{#if !$isLoggedIn}
			<div class="row justify-content-center py-5">
				<div class="col-lg-6 text-center">
					<div class="card shadow-sm border-0 p-5 rounded-4">
						<i class="fa-light fa-user-lock text-danger display-4 mb-3"></i>
						<h3 class="fw-bold mb-2">Sign In Required</h3>
						<p class="text-muted mb-4">Please log in to your account to select a delivery address and complete your checkout.</p>
						<div>
							<a href="/login?redirect=/checkout" class="theme-btn">Log In to Continue</a>
						</div>
					</div>
				</div>
			</div>
		{:else if orderPlacedSuccess}
			<div class="row justify-content-center py-5">
				<div class="col-lg-6 text-center">
					<div class="card shadow-sm border-0 p-5 rounded-4 bg-white">
						<div class="success-icon mb-4">
							<i class="fa-solid fa-circle-check text-success display-2"></i>
						</div>
						<h3 class="fw-bold mb-2">Order Confirmed!</h3>
						<p class="text-muted mb-3">
							Thank you for your purchase. Your order has been placed successfully.
						</p>
						{#if placedTransactionId}
							<div class="badge bg-light text-dark p-2 fs-6 mb-4 border">
								Transaction ID: <strong>{placedTransactionId}</strong>
							</div>
						{/if}
						<div class="d-flex justify-content-center gap-3">
							<a href="/order/history" class="btn btn-outline-dark px-4 py-2">View Orders</a>
							<a href="/shop" class="theme-btn py-2 px-4">Continue Shopping</a>
						</div>
					</div>
				</div>
			</div>
		{:else}
			<div class="row g-4">
				<!-- Left Column: Delivery Options & Details -->
				<div class="col-lg-7">
					<div class="delivery-address-section">
						<!-- Delivery Method Tabs -->
						<div class="delivery-method-tabs mb-4">
							<h3 class="h4 fw-bold mb-3">{$translate('Delivery Method')}</h3>
							<div class="method-options-grid">
								<button
									type="button"
									class="method-card"
									class:active={deliveryMethod === 'COURIER'}
									onclick={() => setDeliveryMethod('COURIER')}
								>
									<div class="method-icon">
										<i class="fa-solid fa-truck-fast"></i>
									</div>
									<div class="method-text">
										<span class="method-title">{$translate('Courier Delivery')}</span>
										<span class="method-desc">
											{cityRates?.delivery_time || '1-2 iş günü'}
										</span>
									</div>
									<div class="method-check">
										<i class="fa-solid fa-circle-check"></i>
									</div>
								</button>

								{#if $features.pickup_points !== false}
								<button
									type="button"
									class="method-card"
									class:active={deliveryMethod === 'PICKUP_POINT'}
									onclick={() => setDeliveryMethod('PICKUP_POINT')}
								>
									<div class="method-icon">
										<i class="fa-solid fa-store"></i>
									</div>
									<div class="method-text">
										<span class="method-title">{$translate('Pickup Points')}</span>
										<span class="method-desc">
											{pickupPointsList.length > 0 ? `${pickupPointsList.length} filial` : 'Gəl-Al'}
										</span>
									</div>
									<div class="method-check">
										<i class="fa-solid fa-circle-check"></i>
									</div>
								</button>
								{/if}

								<button
									type="button"
									class="method-card"
									class:active={deliveryMethod === 'TAKE_FROM_STORE'}
									onclick={() => setDeliveryMethod('TAKE_FROM_STORE')}
								>
									<div class="method-icon">
										<i class="fa-solid fa-bag-shopping"></i>
									</div>
									<div class="method-text">
										<span class="method-title">{$translate('Take From Store')}</span>
										<span class="method-desc text-success fw-bold">{$translate('Free')}</span>
									</div>
									<div class="method-check">
										<i class="fa-solid fa-circle-check"></i>
									</div>
								</button>
							</div>
						</div>

						<!-- Tab 1: Courier Delivery Content -->
						{#if deliveryMethod === 'COURIER'}
							<!-- Courier Speed Selector -->
							<div class="courier-speed-grid mb-4">
								<button
									type="button"
									class="speed-card"
									class:active={courierSpeed === 'STANDARD'}
									onclick={() => setCourierSpeed('STANDARD')}
								>
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<strong class="speed-title">{$translate('Standard Delivery')}</strong>
											<span class="d-block small text-muted">
												{cityRates?.delivery_time || '1-2 iş günü'}
											</span>
										</div>
										<div class="text-end">
											{#if cityRates && preview && preview.delivery === 0 && preview.discounted_total >= Number(cityRates.free_from || 50)}
												<span class="badge bg-success-subtle text-success">{$translate('Free')}</span>
											{:else}
												<span class="fw-bold">₼{Number(cityRates?.price ?? 5).toFixed(2)}</span>
											{/if}
										</div>
									</div>
								</button>

								{#if $features.fast_delivery !== false}
								<button
									type="button"
									class="speed-card"
									class:active={courierSpeed === 'STANDARD_FAST'}
									onclick={() => setCourierSpeed('STANDARD_FAST')}
								>
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<strong class="speed-title d-flex align-items-center gap-1">
												<i class="fa-solid fa-bolt text-warning"></i> {$translate('Fast Delivery')}
											</strong>
											<span class="d-block small text-muted">
												{cityRates?.fast_delivery_time || '2-4 saat'}
											</span>
										</div>
										<div class="text-end">
											<span class="fw-bold">₼{Number(cityRates?.fast_price ?? 10).toFixed(2)}</span>
										</div>
									</div>
								</button>
								{/if}
							</div>

							<!-- Saved Address Dropdown & Preview -->
							<div class="d-flex justify-content-between align-items-center mb-3">
								<h5 class="fw-bold mb-0">Delivery Address</h5>
								{#if savedAddresses.length > 0}
									{#if selectedAddressId}
										<button
											type="button"
											class="btn btn-sm btn-outline-danger"
											onclick={startNewAddress}
										>
											<i class="fa-solid fa-plus me-1"></i> Add New Address
										</button>
									{:else}
										<button
											type="button"
											class="btn btn-sm btn-outline-secondary"
											onclick={() => {
												const def = savedAddresses.find((a) => a.is_default) || savedAddresses[0];
												if (def) selectAddress(def);
											}}
										>
											<i class="fa-solid fa-arrow-left me-1"></i> Use Saved Address
										</button>
									{/if}
								{/if}
							</div>

							{#if addressLoading}
								<div class="text-center py-4">
									<div class="spinner-border text-danger spinner-border-sm" role="status"></div>
									<span class="ms-2 text-muted">Loading addresses...</span>
								</div>
							{:else if savedAddresses.length > 0}
								<div class="address-select-box mb-4">
									<label for="checkout_address_select" class="form-label small fw-semibold text-muted text-uppercase mb-2">Select Delivery Address</label>
									<select
										id="checkout_address_select"
										class="form-select address-select"
										value={selectedAddressId ?? ''}
										onchange={handleAddressSelect}
									>
										{#each savedAddresses as addr (addr.id)}
											<option value={addr.id}>{addressOptionLabel(addr)}</option>
										{/each}
									</select>

									{#if selectedAddressId}
										<div class="selected-address-preview">
											<i class="fa-light fa-location-dot"></i>
											<div>
												<strong>{activeAddressForm.full_name || 'Recipient'}</strong>
												<span>
													{activeAddressForm.contact_number || '-'} · {activeAddressForm.street_building_number}
													{#if activeAddressForm.unit_floor_apartment}, {activeAddressForm.unit_floor_apartment}{/if}
													{#if activeAddressForm.town_village_district}, {activeAddressForm.town_village_district}{/if}, {activeAddressForm.city}
												</span>
											</div>
										</div>
									{/if}
								</div>
							{:else}
								<div class="address-warning mb-4" role="alert">
									<i class="fa-light fa-circle-exclamation"></i>
									<div>
										<strong>No delivery address found.</strong>
										<span>Add an address below before placing your order.</span>
									</div>
								</div>
							{/if}

							<!-- Address Form Inputs (Only shown when adding a new address) -->
							{#if !selectedAddressId}
								<div class="card border-0 bg-light p-4 rounded-3 mb-4">
									<div class="d-flex justify-content-between align-items-center mb-3">
										<h5 class="fw-bold mb-0">
											{$translate('New Address Information')}
										</h5>
									</div>

									<form onsubmit={saveAndUseAddress}>
										<div class="row g-3">
											<div class="col-md-6 form-group">
												<label class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
												<input
													type="text"
													class="form-control"
													placeholder="Recipient full name"
													bind:value={activeAddressForm.full_name}
													required
												/>
											</div>

											<div class="col-md-6 form-group">
												<label class="form-label small fw-semibold text-secondary">Phone Number <span class="text-danger">*</span></label>
												<input
													type="tel"
													class="form-control"
													placeholder="e.g. 0501234567"
													bind:value={activeAddressForm.contact_number}
													required
												/>
											</div>

											<div class="col-md-6 form-group">
												<label class="form-label small fw-semibold text-secondary">City / Region <span class="text-danger">*</span></label>
												<select
													class="form-select"
													bind:value={activeAddressForm.city}
													onchange={(e) => updateCityRates(e.currentTarget.value)}
													required
												>
													{#each availableCityNames as c}
														<option value={c}>{c}</option>
													{/each}
												</select>
											</div>

											<div class="col-md-6 form-group">
												<label class="form-label small fw-semibold text-secondary">Rayon / District <span class="text-danger">*</span></label>
												<input
													type="text"
													class="form-control"
													placeholder="e.g. Yasamal, Nasimi"
													bind:value={activeAddressForm.town_village_district}
													required
												/>
											</div>

											<div class="col-md-8 form-group">
												<label class="form-label small fw-semibold text-secondary">Street & Building <span class="text-danger">*</span></label>
												<input
													type="text"
													class="form-control"
													placeholder="e.g. Nizami str. 42"
													bind:value={activeAddressForm.street_building_number}
													required
												/>
											</div>

											<div class="col-md-4 form-group">
												<label class="form-label small fw-semibold text-secondary">Apartment / Unit</label>
												<input
													type="text"
													class="form-control"
													placeholder="Apt 12, Floor 4"
													bind:value={activeAddressForm.unit_floor_apartment}
												/>
											</div>

											<div class="col-md-12 form-group">
												<label class="form-label small fw-semibold text-secondary">Address Label (Optional)</label>
												<input
													type="text"
													class="form-control"
													placeholder="e.g. Home, Office"
													bind:value={activeAddressForm.location_label}
												/>
											</div>

											<div class="col-12 mt-2">
												<div class="form-check">
													<input
														type="checkbox"
														class="form-check-input"
														id="is_default_checkout"
														bind:checked={activeAddressForm.is_default}
													/>
													<label class="form-check-label small" for="is_default_checkout">
														Save and set as my default delivery address
													</label>
												</div>
											</div>

											<div class="col-12 mt-3 d-flex gap-2">
												<button
													type="submit"
													class="theme-btn btn-sm py-2 px-4"
													disabled={savingAddress}
												>
													{savingAddress ? 'Saving Address...' : 'Save & Deliver to This Address'}
												</button>
												{#if savedAddresses.length > 0}
													<button
														type="button"
														class="btn btn-sm btn-outline-secondary py-2 px-3"
														onclick={() => {
															const def = savedAddresses.find((a) => a.is_default) || savedAddresses[0];
															if (def) selectAddress(def);
														}}
													>
														Cancel
													</button>
												{/if}
											</div>
										</div>
									</form>
								</div>
							{/if}

						<!-- Tab 2: Pickup Points Content -->
						{:else if deliveryMethod === 'PICKUP_POINT'}
							<div class="pickup-points-section mb-4">
								<h5 class="fw-bold mb-3">{$translate('Select Pickup Point')}</h5>
								{#if pickupPointsLoading}
									<div class="text-center py-4">
										<div class="spinner-border spinner-border-sm text-danger" role="status"></div>
										<span class="ms-2 text-muted">{$translate('Filiallar yüklənir...')}</span>
									</div>
								{:else if pickupPointsList.length === 0}
									<div class="alert alert-warning">
										{$translate('Aktiv təhvil məntəqəsi tapılmadı.')}
									</div>
								{:else}
									<div class="pickup-points-grid">
										{#each pickupPointsList as pp (pp.id)}
											{@const isSelected = selectedPickupPointId === pp.id}
											{@const delTime = getLocalizedDeliveryTime(pp.delivery_time, $locale)}
											<div
												class="pickup-point-card"
												class:selected={isSelected}
												onclick={() => selectPickupPoint(pp.id)}
												role="button"
												tabindex="0"
												onkeydown={(e) => e.key === 'Enter' && selectPickupPoint(pp.id)}
											>
												<div class="pp-radio">
													<input
														type="radio"
														name="pickup_point_radio"
														checked={isSelected}
														id={`pp_${pp.id}`}
														onchange={() => selectPickupPoint(pp.id)}
													/>
												</div>
												<div class="pp-content">
													<div class="d-flex justify-content-between align-items-start gap-2 mb-1">
														<strong class="pp-name">{pp.name}</strong>
														<span class="badge" class:bg-success-subtle={pp.price === 0} class:text-success={pp.price === 0} class:bg-light={pp.price > 0} class:text-dark={pp.price > 0}>
															{pp.price === 0 ? $translate('Free') : `₼${Number(pp.price).toFixed(2)}`}
														</span>
													</div>
													<p class="pp-address mb-1">
														<i class="fa-solid fa-location-dot text-danger me-1"></i>
														{pp.address}
													</p>
													{#if delTime}
														<div class="pp-time small text-muted">
															<i class="fa-regular fa-clock me-1"></i>
															{$translate('Estimated delivery')}: {delTime}
														</div>
													{/if}
												</div>
											</div>
										{/each}
									</div>
								{/if}
							</div>

						<!-- Tab 3: Take From Store Content -->
						{:else if deliveryMethod === 'TAKE_FROM_STORE'}
							<div class="store-pickup-card card border-0 bg-light p-4 rounded-3 mb-4">
								<div class="d-flex align-items-start gap-3">
									<div class="store-icon">
										<i class="fa-solid fa-shop"></i>
									</div>
									<div>
										<h5 class="fw-bold mb-1">{$translate('Central Store')}</h5>
										<p class="text-muted mb-2">
											<i class="fa-solid fa-location-dot text-danger me-1"></i>
											{$translate('Bakı şəhəri, Nizami küçəsi 45, ShopEra Showroom')}
										</p>
										<div class="small text-muted mb-2">
											<i class="fa-regular fa-clock me-1"></i>
											{$translate('İş saatları: Hər gün 10:00 - 21:00')}
										</div>
										<span class="badge bg-success-subtle text-success border border-success-subtle">
											<i class="fa-solid fa-check me-1"></i> 0.00 ₼ — {$translate('Free')}
										</span>
									</div>
								</div>
							</div>
						{/if}

						<!-- Order Notes (Shared) -->
						<div class="form-group mb-4">
							<label for="order_notes" class="form-label fw-bold">Order Notes (Optional)</label>
							<textarea
								id="order_notes"
								rows="3"
								class="form-control"
								placeholder="Special instructions for delivery (e.g. building gate code, drop-off note)..."
								bind:value={orderNotes}
							></textarea>
						</div>
					</div>
				</div>

				<!-- Right Column: Order Summary & Payment -->
				<div class="col-lg-5">
					<div class="card border p-4 rounded-4 shadow-sm mb-4">
						<h4 class="fw-bold mb-3">Order Summary</h4>

						<!-- Basket Items List -->
						<div class="order-items-list mb-3" style="max-height: 280px; overflow-y: auto;">
							{#if preview?.items && preview.items.length > 0}
								{#each preview.items as item (item.id)}
									<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
										<div class="d-flex align-items-center gap-3">
											<div class="position-relative">
												<span class="badge bg-secondary position-absolute top-0 start-100 translate-middle rounded-pill">
													{item.quantity}
												</span>
											</div>
											<div>
												<h6 class="mb-0 fw-semibold text-truncate" style="max-width: 220px;" title={item.title}>
													{item.title}
												</h6>
												<span class="small text-muted">₼{Number(item.discounted_price || item.original_price).toFixed(2)} each</span>
											</div>
										</div>
										<span class="fw-bold">₼{Number(item.total).toFixed(2)}</span>
									</div>
								{/each}
							{:else}
								{#each $basketItems as item (item.id)}
									<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
										<div class="d-flex align-items-center gap-3">
											<img
												src={getProductImage(item)}
												alt={getProductTitle(item)}
												width="46"
												height="46"
												class="rounded object-fit-cover border"
											/>
											<div>
												<h6 class="mb-0 fw-semibold text-truncate" style="max-width: 200px;" title={getProductTitle(item)}>
													{getProductTitle(item)}
												</h6>
												<span class="small text-muted">Qty: {item.quantity} × ₼{Number(item.retail_unit_price ?? 0).toFixed(2)}</span>
											</div>
										</div>
										<span class="fw-bold">₼{Number(item.retail_total ?? 0).toFixed(2)}</span>
									</div>
								{/each}
							{/if}
						</div>

						<!-- Promo Code Box -->
						<div class="promo-box mb-4">
							{#if $appliedPromo}
								<div class="alert alert-success d-flex justify-content-between align-items-center py-2 px-3 mb-0">
									<div class="small">
										<i class="fa-solid fa-tag me-1"></i>
										Promo code <strong>{$appliedPromo.code}</strong> applied ({$appliedPromo.discount_percent}% off)
									</div>
									<button
										type="button"
										class="btn btn-sm btn-link text-danger p-0 ms-2"
										onclick={handleRemovePromo}
									>
										<i class="fa-solid fa-xmark"></i> Remove
									</button>
								</div>
							{:else}
								{#if $features.promo_codes !== false}
								<form onsubmit={handleApplyPromo} class="d-flex gap-2">
									<input
										type="text"
										class="form-control form-control-sm"
										placeholder="Have a promo code?"
										bind:value={promoCodeInput}
										disabled={promoLoading}
									/>
									<button
										type="submit"
										class="btn btn-dark btn-sm text-nowrap px-3"
										disabled={promoLoading || !promoCodeInput.trim()}
									>
										{promoLoading ? 'Checking...' : 'Apply'}
									</button>
								</form>
								{/if}
								{#if promoError}
									<div class="small text-danger mt-1">{promoError}</div>
								{/if}
							{/if}
						</div>

						<!-- Calculation Breakdown -->
						<div class="price-breakdown border-top pt-3 mb-4">
							<div class="d-flex justify-content-between mb-2">
								<span class="text-muted">Subtotal</span>
								<span class="fw-semibold">
									₼{Number(preview?.main_amount ?? $basketTotal).toFixed(2)}
								</span>
							</div>

							{#if preview && preview.discount_amount > 0}
								<div class="d-flex justify-content-between mb-2 text-success">
									<span>Discount</span>
									<span class="fw-semibold">-₼{Number(preview.discount_amount).toFixed(2)}</span>
								</div>
							{/if}

							<div class="d-flex justify-content-between mb-2">
								<span class="text-muted">
									{$translate('Delivery Method')}
									<small class="badge bg-light text-dark ms-1">
										{deliveryMethod === 'PICKUP_POINT' ? $translate('Pickup Points') : deliveryMethod === 'TAKE_FROM_STORE' ? $translate('Take From Store') : $translate('Courier Delivery')}
									</small>
								</span>
								<span class="fw-semibold">
									{#if previewLoading}
										<span class="spinner-border spinner-border-sm text-muted" role="status"></span>
									{:else if preview}
										{#if preview.delivery > 0}
											₼{Number(preview.delivery).toFixed(2)}
										{:else}
											<span class="text-success fw-bold">{$translate('Free')}</span>
										{/if}
									{:else if deliveryMethod === 'TAKE_FROM_STORE'}
										<span class="text-success fw-bold">{$translate('Free')}</span>
									{:else}
										<span class="text-muted small">Select options</span>
									{/if}
								</span>
							</div>

							{#if preview?.delivery_info?.free_from && preview.delivery > 0}
								<div class="small text-muted mb-2 fst-italic">
									* Free delivery on orders over ₼{Number(preview.delivery_info.free_from).toFixed(2)}
								</div>
							{/if}

							<div class="d-flex justify-content-between pt-3 border-top mt-2">
								<span class="h5 fw-bold mb-0">Total</span>
								<span class="h5 fw-bold text-danger mb-0">
									{#if preview}
										₼{Number(preview.total).toFixed(2)}
									{:else}
										₼{Number($basketTotal).toFixed(2)}
									{/if}
								</span>
							</div>
						</div>

						{#if previewError}
							<div class="alert alert-warning py-2 small mb-3">
								<i class="fa-solid fa-triangle-exclamation me-1"></i> {previewError}
							</div>
						{/if}

						<!-- Payment Methods Selection -->
						<div class="payment-methods mb-4">
							<h6 class="fw-bold mb-3">Payment Method</h6>
							<div class="d-flex flex-column gap-2">
								<label
									class="card p-3 border cursor-pointer d-flex flex-row align-items-center justify-content-between"
									class:border-danger={paymentType === 'CARD'}
									class:bg-light={paymentType === 'CARD'}
									class:d-none={!canCard}
									style="cursor: pointer;"
								>
									<div class="d-flex align-items-center gap-2">
										<input
											type="radio"
											name="payment_choice"
											value="CARD"
											checked={paymentType === 'CARD'}
											onchange={() => (paymentType = 'CARD')}
											class="form-check-input mt-0"
										/>
										<div>
											<span class="fw-semibold d-block">Online Card Payment</span>
											<span class="small text-muted">Visa, Mastercard, EPoint</span>
										</div>
									</div>
									<div class="d-flex gap-1 text-muted fs-5">
										<i class="fa-brands fa-cc-visa text-primary"></i>
										<i class="fa-brands fa-cc-mastercard text-danger"></i>
									</div>
								</label>

								<label
									class="card p-3 border cursor-pointer d-flex flex-row align-items-center justify-content-between"
									class:border-danger={paymentType === 'CASH'}
									class:bg-light={paymentType === 'CASH'}
									class:d-none={!canCash}
									style="cursor: pointer;"
								>
									<div class="d-flex align-items-center gap-2">
										<input
											type="radio"
											name="payment_choice"
											value="CASH"
											checked={paymentType === 'CASH'}
											onchange={() => (paymentType = 'CASH')}
											class="form-check-input mt-0"
										/>
										<div>
											<span class="fw-semibold d-block">Cash on Delivery</span>
											<span class="small text-muted">Pay at doorstep upon receiving</span>
										</div>
									</div>
									<div class="text-muted fs-5">
										<i class="fa-light fa-money-bill-wave text-success"></i>
									</div>
								</label>

								<label
									class="card p-3 border cursor-pointer d-flex flex-row align-items-center justify-content-between"
									class:border-danger={paymentType === 'WHATSAPP'}
									class:bg-light={paymentType === 'WHATSAPP'}
									class:d-none={!canWhatsapp}
									style="cursor: pointer;"
								>
									<div class="d-flex align-items-center gap-2">
										<input
											type="radio"
											name="payment_choice"
											value="WHATSAPP"
											checked={paymentType === 'WHATSAPP'}
											onchange={() => (paymentType = 'WHATSAPP')}
											class="form-check-input mt-0"
										/>
										<div>
											<span class="fw-semibold d-block">{$translate('Order via WhatsApp')}</span>
											<span class="small text-muted">{$translate('Confirm the order on WhatsApp')}</span>
										</div>
									</div>
									<div class="text-success fs-5">
										<i class="fa-brands fa-whatsapp"></i>
									</div>
								</label>
							</div>
						</div>

						{#if submitError}
							<div class="alert alert-danger py-2 small mb-3">
								<i class="fa-solid fa-circle-exclamation me-1"></i> {submitError}
							</div>
						{/if}

						<!-- Submit Button -->
						<button
							type="button"
							class="theme-btn w-100 py-3 text-center fs-6 fw-bold"
							onclick={paymentType === 'WHATSAPP' ? orderViaWhatsapp : handlePlaceOrder}
							disabled={paymentType === 'WHATSAPP' ? waBusy : (submitting || !isReadyToPlace)}
						>
							{#if paymentType === 'WHATSAPP'}
								<i class="fa-brands fa-whatsapp me-2"></i> {waBusy ? $translate('Preparing...') : $translate('Order via WhatsApp')}
							{:else if submitting}
								<span class="spinner-border spinner-border-sm me-2" role="status"></span>
								Processing Order...
							{:else if paymentType === 'CARD'}
								<i class="fa-solid fa-lock me-2"></i> Proceed to Card Payment
							{:else}
								<i class="fa-solid fa-check me-2"></i> Place Order (Cash)
							{/if}
						</button>

						{#if waError}
							<p class="text-danger small mt-2 mb-0">{waError}</p>
						{/if}


						<div class="text-center mt-3">
							<span class="small text-muted">
								<i class="fa-light fa-shield-check me-1 text-success"></i> Safe and secure checkout
							</span>
						</div>
					</div>
				</div>
			</div>
		{/if}
	</div>
</div>

<style>
	/* Delivery Method Tabs */
	.method-options-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 12px;
	}

	@media (max-width: 767.98px) {
		.delivery-method-tabs {
			margin-bottom: 14px !important;
		}

		.delivery-method-tabs h3 {
			font-size: 1.1rem;
			margin-bottom: 10px !important;
		}

		.method-options-grid {
			grid-template-columns: 1fr;
			gap: 8px;
		}

		.method-card {
			flex-direction: row !important;
			align-items: center !important;
			padding: 10px 14px !important;
			gap: 12px !important;
			border-radius: 12px !important;
		}

		.method-icon {
			width: 36px !important;
			height: 36px !important;
			font-size: 15px !important;
			margin-bottom: 0 !important;
			border-radius: 10px !important;
			flex-shrink: 0;
		}

		.method-text {
			flex: 1;
			min-width: 0;
			gap: 2px;
		}

		.method-title {
			font-size: 13.5px !important;
			line-height: 1.25;
		}

		.method-desc {
			font-size: 11.5px !important;
		}

		.method-check {
			position: static !important;
			font-size: 16px !important;
			flex-shrink: 0;
			margin-left: auto;
		}

		.courier-speed-grid {
			gap: 8px;
			margin-bottom: 14px !important;
		}

		.speed-card {
			padding: 10px 14px !important;
			border-radius: 10px !important;
		}

		.speed-title {
			font-size: 13px !important;
		}

		.pickup-point-card {
			padding: 12px 14px !important;
			border-radius: 12px !important;
			gap: 10px !important;
		}

		.pp-name {
			font-size: 13.5px !important;
		}

		.pp-address,
		.pp-delivery {
			font-size: 11.5px !important;
		}
	}

	.method-card {
		position: relative;
		display: flex;
		flex-direction: column;
		align-items: flex-start;
		padding: 16px;
		background: #ffffff;
		border: 2px solid #e2e8f0;
		border-radius: 16px;
		text-align: left;
		cursor: pointer;
		transition: all 0.25s ease;
	}

	.method-card:hover {
		border-color: #cbd5e1;
		transform: translateY(-2px);
		box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
	}

	.method-card.active {
		border-color: var(--theme, #fd3d57);
		background: color-mix(in srgb, var(--theme, #fd3d57) 4%, #fff);
		box-shadow: 0 4px 16px rgba(253, 61, 87, 0.12);
	}

	.method-icon {
		width: 40px;
		height: 40px;
		border-radius: 10px;
		background: #f1f5f9;
		color: #334155;
		font-size: 18px;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 10px;
		transition: all 0.2s ease;
	}

	.method-card.active .method-icon {
		background: var(--theme, #fd3d57);
		color: #ffffff;
	}

	.method-text {
		display: flex;
		flex-direction: column;
		gap: 2px;
	}

	.method-title {
		font-size: 14px;
		font-weight: 700;
		color: #0f172a;
	}

	.method-desc {
		font-size: 12px;
		color: #64748b;
	}

	.method-check {
		position: absolute;
		top: 14px;
		right: 14px;
		font-size: 16px;
		color: #cbd5e1;
		transition: color 0.2s ease;
	}

	.method-card.active .method-check {
		color: var(--theme, #fd3d57);
	}

	/* Courier Speed Grid */
	.courier-speed-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 12px;
	}

	@media (max-width: 575.98px) {
		.courier-speed-grid {
			grid-template-columns: 1fr;
		}
	}

	.speed-card {
		padding: 14px 16px;
		background: #ffffff;
		border: 1.5px solid #e2e8f0;
		border-radius: 12px;
		text-align: left;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.speed-card:hover {
		border-color: #cbd5e1;
	}

	.speed-card.active {
		border-color: var(--theme, #fd3d57);
		background: color-mix(in srgb, var(--theme, #fd3d57) 4%, #fff);
	}

	.speed-title {
		font-size: 13px;
		color: #0f172a;
	}

	/* Pickup Points Grid */
	.pickup-points-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 12px;
	}

	@media (max-width: 575.98px) {
		.pickup-points-grid {
			grid-template-columns: 1fr;
		}
	}

	.pickup-point-card {
		display: flex;
		align-items: flex-start;
		gap: 12px;
		padding: 16px;
		background: #ffffff;
		border: 1.5px solid #e2e8f0;
		border-radius: 14px;
		cursor: pointer;
		transition: all 0.2s ease;
	}

	.pickup-point-card:hover {
		border-color: #cbd5e1;
		transform: translateY(-1px);
	}

	.pickup-point-card.selected {
		border-color: var(--theme, #fd3d57);
		background: color-mix(in srgb, var(--theme, #fd3d57) 4%, #fff);
		box-shadow: 0 4px 14px rgba(253, 61, 87, 0.1);
	}

	.pp-radio input {
		margin-top: 3px;
		cursor: pointer;
	}

	.pp-content {
		flex: 1;
		min-width: 0;
	}

	.pp-name {
		font-size: 14px;
		color: #0f172a;
	}

	.pp-address {
		font-size: 12px;
		color: #64748b;
		line-height: 1.4;
	}

	/* Store Pickup Card */
	.store-pickup-card {
		border: 1.5px solid #e2e8f0 !important;
	}

	.store-icon {
		width: 48px;
		height: 48px;
		border-radius: 12px;
		background: color-mix(in srgb, var(--theme, #fd3d57) 10%, #fff);
		color: var(--theme, #fd3d57);
		font-size: 22px;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
	}

	/* Address Selector Box */
	.address-select-box {
		border: 1px solid #e7edf5;
		border-radius: 14px;
		background: #fff;
		padding: 18px;
		box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
	}

	.address-select {
		min-height: 52px;
		border-color: #dbe4ef;
		border-radius: 12px;
		color: #111827;
		font-weight: 700;
		box-shadow: none;
	}

	.address-select:focus {
		border-color: var(--theme, #fd3d57);
		box-shadow: 0 0 0 4px rgba(253, 61, 87, 0.12);
	}

	.selected-address-preview,
	.address-warning {
		display: flex;
		gap: 12px;
		align-items: flex-start;
		margin-top: 14px;
		border-radius: 12px;
		padding: 14px 16px;
	}

	.selected-address-preview {
		background: #f4fbf7;
		color: #233447;
	}

	.selected-address-preview i {
		color: #16a34a;
		margin-top: 3px;
	}

	.selected-address-preview strong,
	.selected-address-preview span,
	.address-warning strong,
	.address-warning span {
		display: block;
	}

	.selected-address-preview span,
	.address-warning span {
		color: #64748b;
		font-size: 0.92rem;
		line-height: 1.45;
	}

	.address-warning {
		border: 1px solid #fed7aa;
		background: #fff7ed;
		color: #9a3412;
	}

	.address-warning i {
		color: #f97316;
		font-size: 1.2rem;
		margin-top: 2px;
	}

	.btn-whatsapp {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		border: 0;
		border-radius: 12px;
		background: #25d366;
		color: #fff;
		font-weight: 700;
		font-size: 16px;
		cursor: pointer;
		transition: background 0.2s ease;
	}
	.btn-whatsapp:hover:not(:disabled) { background: #1ebe5b; }
	.btn-whatsapp:disabled { opacity: 0.7; cursor: default; }
</style>
