<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { goto } from '$app/navigation';
	import { user, isLoggedIn, logout } from '$lib/services/auth';
	import { fetchAddresses, uploadAvatar, type ApiAddress } from '$lib/services/account';
	import Account from '$lib/components/pages/settings/Account.svelte';
	import Orders from '$lib/components/pages/dashboard/Orders.svelte';
	import OrderDetail from '$lib/components/pages/dashboard/OrderDetail.svelte';
	import { selectedOrder } from '$lib/services/orders';
	import AddressSection from '$lib/components/pages/dashboard/AddressSection.svelte';
	import Chat from '$lib/components/pages/dashboard/Chat.svelte';
	import PlanLimits from '$lib/components/pages/dashboard/PlanLimits.svelte';
	import { features as featureFlags } from '$lib/services/features';

	type TabKey = 'dashboard' | 'order-history' | 'order-details' | 'messages' | 'wishlist' | 'addresses' | 'plan' | 'settings' | 'logout';
	let tab = $state<TabKey>('dashboard');

	let defaultAddress = $state<ApiAddress | null>(null);
	let loadingAddress = $state(false);
	let uploadingAvatar = $state(false);
	let avatarError = $state<string | null>(null);

	async function handleAvatarChange(event: Event) {
		const input = event.target as HTMLInputElement;
		const file = input.files?.[0];
		if (!file) return;

		if (file.size > 5 * 1024 * 1024) {
			avatarError = 'Image size must be less than 5MB.';
			input.value = '';
			return;
		}

		const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
		if (!validTypes.includes(file.type)) {
			avatarError = 'Please select a JPG, PNG, or WEBP image.';
			input.value = '';
			return;
		}

		try {
			uploadingAvatar = true;
			avatarError = null;
			await uploadAvatar(file);
		} catch (e) {
			avatarError = e instanceof Error ? e.message : 'Failed to update avatar.';
		} finally {
			uploadingAvatar = false;
			input.value = '';
		}
	}

	async function loadDefaultAddress() {
		if (!$isLoggedIn) return;
		try {
			loadingAddress = true;
			const list = await fetchAddresses();
			defaultAddress = list.find((a) => a.is_default) ?? list[0] ?? null;
		} catch {
			defaultAddress = null;
		} finally {
			loadingAddress = false;
		}
	}

	onMount(() => {
		loadDefaultAddress();
	});
</script>

<!-- Dashboard Section Start -->
<div class="dashboard-section section-padding fix account-dashboard">
<div class="container">
<div class="row g-4 account-dashboard-grid">
<div class="col-xl-3">
    <div class="dashboard-navigation-sidebar">
        <h3>{$translate('Navigation')}</h3>
        <div>
            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist"
                aria-orientation="vertical">
                <button class="nav-link" class:active={tab === 'dashboard'}
                    id="v-pills-dashboard-tab" type="button" role="tab" aria-controls="v-pills-dashboard"
                    aria-selected={tab === 'dashboard'} onclick={() => (tab = 'dashboard')}> <i
                        class="fa-sharp fa-solid fa-grid-2"></i>{$translate('Dashboard')}</button>

                <button class="nav-link" class:active={tab === 'order-history'}
                    id="v-pills-order-history-tab" type="button" role="tab" aria-controls="v-pills-order-history"
                    aria-selected={tab === 'order-history'} onclick={() => (tab = 'order-history')}> <i
                        class="fa-solid fa-sync"></i>{$translate('Order History')}</button>

                <button class="nav-link" class:active={tab === 'order-details'}
                    id="v-pills-order-details-tab" type="button" role="tab" aria-controls="v-pills-order-details"
                    aria-selected={tab === 'order-details'} onclick={() => (tab = 'order-details')}><i
                        class="fa-solid fa-list"></i>{$translate('Order Details')}</button>

                {#if $featureFlags.chat}
                    <button class="nav-link" class:active={tab === 'messages'}
                        id="v-pills-messages-tab" type="button" role="tab" aria-controls="v-pills-messages"
                        aria-selected={tab === 'messages'} onclick={() => (tab = 'messages')}><i
                            class="fa-solid fa-comments"></i>{$translate('Messages')}</button>
                {/if}

                <button class="nav-link" class:active={tab === 'wishlist'}
                    id="v-pills-wishlist-tab" type="button" role="tab" aria-controls="v-pills-wishlist"
                    aria-selected={tab === 'wishlist'} onclick={() => (tab = 'wishlist')}><i
                        class="fa-light fa-heart"></i>{$translate('Wishlist')}</button>

                <button class="nav-link" class:active={tab === 'addresses'}
                    id="v-pills-addresses-tab" type="button" role="tab" aria-controls="v-pills-addresses"
                    aria-selected={tab === 'addresses'} onclick={() => (tab = 'addresses')}><i
                        class="fa-solid fa-location-dot"></i>{$translate('Addresses')}</button>

                <button class="nav-link" class:active={tab === 'plan'}
                    id="v-pills-plan-tab" type="button" role="tab" aria-controls="v-pills-plan"
                    aria-selected={tab === 'plan'} onclick={() => (tab = 'plan')}><i
                        class="fa-solid fa-gauge-high"></i>{$translate('Plan & Limits')}</button>

                <button class="nav-link" class:active={tab === 'settings'}
                    id="v-pills-settings-tab" type="button" role="tab" aria-controls="v-pills-settings"
                    aria-selected={tab === 'settings'} onclick={() => (tab = 'settings')}><i
                        class="fa-regular fa-gear"></i>{$translate('Settings')}</button>

                <button class="nav-link" class:active={tab === 'logout'}
                    id="v-pills-logout-tab" type="button" role="tab" aria-controls="v-pills-logout"
                    aria-selected={tab === 'logout'} onclick={() => (tab = 'logout')}><i
                        class="fa-solid fa-sign-out-alt"></i>{$translate('Log Out')}</button>
            </div>
        </div>
    </div>
</div>
<div class="col-xl-9">

    <div class="tab-content" id="v-pills-tabContent">
        <div class="tab-pane fade" class:show={tab === 'dashboard'} class:active={tab === 'dashboard'} id="v-pills-dashboard" role="tabpanel"
            aria-labelledby="v-pills-dashboard-tab" tabindex="0">
            <div class="dashboard-wrapper">
                <div class="dashboard-top">
                    <div class="row g-4 align-items-stretch">
                        <div class="col-md-6 col-12">
                            <div class="dashboard-profile h-100 d-flex flex-column justify-content-center align-items-center">
                                <div class="thumb avatar-thumb position-relative">
                                    <img
                                        src={$user?.avatar || '/assets/images/dashboard/dashboard-profileThumb.jpg'}
                                        alt="Profile Avatar"
                                        class="avatar-img"
                                    />
                                    <label class="avatar-camera-btn" title="Change photo" aria-label="Change photo">
                                        <i class="fa-solid fa-camera"></i>
                                        <input
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                            class="d-none"
                                            onchange={handleAvatarChange}
                                            disabled={uploadingAvatar}
                                        />
                                    </label>
                                    {#if uploadingAvatar}
                                        <div class="avatar-loading-overlay">
                                            <i class="fa-solid fa-spinner fa-spin"></i>
                                        </div>
                                    {/if}
                                </div>
                                {#if avatarError}
                                    <small class="text-danger mb-2 text-center">{avatarError}</small>
                                {/if}
                                <h3>{$user?.name ?? 'My Account'}</h3>
                                <p>{$user?.email ?? ($user?.phone ?? 'Customer')}</p>
                                <a href="#settings" onclick={(e) => { e.preventDefault(); tab = 'settings'; }}>Edit Profile</a>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="dashboard-profile-info h-100 d-flex flex-column justify-content-center">
                                <h6>Default Address</h6>
                                {#if defaultAddress}
                                    <h5>{defaultAddress.full_name}</h5>
                                    <a href="#addresses" class="address" style="max-width: 100%;" onclick={(e) => { e.preventDefault(); tab = 'addresses'; }}>
                                        {defaultAddress.street_building_number}{#if defaultAddress.unit_floor_apartment}, {defaultAddress.unit_floor_apartment}{/if},
                                        {defaultAddress.town_village_district}, {defaultAddress.city}
                                    </a>
                                    {#if defaultAddress.contact_number}
                                        <a href="tel:{defaultAddress.contact_number}" class="phone">{defaultAddress.contact_number}</a>
                                    {/if}
                                    <button type="button" class="edit border-0 bg-transparent p-0 text-start" onclick={() => (tab = 'addresses')}>Manage Addresses</button>
                                {:else if loadingAddress}
                                    <p class="text-muted mt-2">Loading address...</p>
                                {:else}
                                    <p class="text-muted mt-2 mb-3">No delivery address saved yet.</p>
                                    <button type="button" class="edit border-0 bg-transparent p-0 text-start" onclick={() => (tab = 'addresses')}>+ Add Address</button>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="order-history">
                    <div class="header">
                        <h2>Recent Order History</h2>
                        <a href="#" class="view-all">View All</a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ORDER ID</th>
                                <th>DATE</th>
                                <th>TOTAL</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#738</td>
                                <td>8 Sep, 2024</td>
                                <td>$135.00 (5 Products)</td>
                                <td><span class="status processing">Processing</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#703</td>
                                <td>24 May, 2024</td>
                                <td>$25.00 (1 Product)</td>
                                <td><span class="status on-the-way">On the way</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#130</td>
                                <td>22 Oct, 2024</td>
                                <td>$250.00 (4 Products)</td>
                                <td><span class="status completed">Completed</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#561</td>
                                <td>1 Feb, 2024</td>
                                <td>$35.00 (1 Product)</td>
                                <td><span class="status completed">Completed</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#536</td>
                                <td>21 Sep, 2024</td>
                                <td>$578.00 (13 Products)</td>
                                <td><span class="status completed">Completed</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                            <tr>
                                <td>#492</td>
                                <td>22 Oct, 2024</td>
                                <td>$345.00 (7 Products)</td>
                                <td><span class="status completed">Completed</span> <a href="#">View
                                        Details</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" class:show={tab === 'order-history'} class:active={tab === 'order-history'} id="v-pills-order-history" role="tabpanel"
            aria-labelledby="v-pills-order-history-tab" tabindex="0">
                <Orders onSelect={(order) => { selectedOrder.set(order); tab = 'order-details'; }} />
            </div>
        <div class="tab-pane fade" class:show={tab === 'order-details'} class:active={tab === 'order-details'} id="v-pills-order-details" role="tabpanel"
            aria-labelledby="v-pills-order-details-tab" tabindex="0">
                <OrderDetail order={$selectedOrder} onBack={() => (tab = 'order-history')} />
            </div>
        {#if $featureFlags.chat}
        <div class="tab-pane fade" class:show={tab === 'messages'} class:active={tab === 'messages'} id="v-pills-messages" role="tabpanel"
            aria-labelledby="v-pills-messages-tab" tabindex="0">
                <Chat />
            </div>
        {/if}
        <div class="tab-pane fade" class:show={tab === 'wishlist'} class:active={tab === 'wishlist'} id="v-pills-wishlist" role="tabpanel"
            aria-labelledby="v-pills-wishlist-tab" tabindex="0">
<!-- Wishlist Section Start -->
            <!-- Wishlist Section Start -->
            <div class="wishlist-wrapper fix bg-white">
                <div class="container">
                    <form action="#" class="woocommerce-cart-form">
                        <table class="wishlist_table">
                            <thead>
                                <tr>
                                    <th class="cart-col-image">{$translate('Product')}</th>
                                    <th class="cart-col-price">{$translate('Price')}</th>
                                    <th class="cart-col-total">Sub total</th>
                                    <th class="cart-col-stock">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="cart_item">
                                    <td class="product" data-title="Product">
                                        <button><i class="fa-solid fa-xmark"></i></button>
                                        <a class="cartimage" href="#"><img width="91"
                                                height="91" src="/assets/images/cart/cart-thumb1_1.jpg"
                                                alt="Image"></a>
                                        shorter dress above
                                    </td>
                                    <td data-title="Price">
                                        <span class="amount"><bdi><span>$</span>30.00</bdi></span>
                                    </td>
                                    <td data-title="Total">
                                        <span class="amount"><bdi><span>$</span>20.00</bdi></span>
                                    </td>
                                    <td data-title="stock">
                                        <a href="#" class="stock">In stock</a>
                                    </td>
                                </tr>
                                <tr class="cart_item">
                                    <td class="product" data-title="Product">
                                        <button><i class="fa-solid fa-xmark"></i></button>
                                        <a class="cartimage" href="#"><img width="91"
                                                height="91" src="/assets/images/cart/cart-thumb1_2.jpg"
                                                alt="Image"></a>
                                        close-fitting dress
                                    </td>
                                    <td data-title="Price">
                                        <span class="amount"><bdi><span>$</span>50.00</bdi></span>
                                    </td>
                                    <td data-title="Total">
                                        <span class="amount"><bdi><span>$</span>60.00</bdi></span>
                                    </td>
                                    <td data-title="stock">
                                        <a href="#" class="stock_out">out of stock</a>
                                    </td>
                                </tr>
                                <tr class="cart_item">
                                    <td class="product" data-title="Product">
                                        <button><i class="fa-solid fa-xmark"></i></button>
                                        <a class="cartimage" href="#"><img width="91"
                                                height="91" src="/assets/images/cart/cart-thumb1_3.jpg"
                                                alt="Image"></a>
                                        flowy dress that often
                                    </td>
                                    <td data-title="Price">
                                        <span class="amount"><bdi><span>$</span>70.00</bdi></span>
                                    </td>
                                    <td data-title="Total">
                                        <span class="amount"><bdi><span>$</span>30.00</bdi></span>
                                    </td>
                                    <td data-title="stock">
                                        <a href="#" class="stock">In stock</a>
                                    </td>
                                </tr>
                                <tr class="cart_item">
                                    <td class="product" data-title="Product">
                                        <button><i class="fa-solid fa-xmark"></i></button>
                                        <a class="cartimage" href="#"><img width="91"
                                                height="91" src="/assets/images/cart/cart-thumb1_4.jpg"
                                                alt="Image"></a>
                                        Tight and form
                                    </td>
                                    <td data-title="Price">
                                        <span class="amount"><bdi><span>$</span>10.00</bdi></span>
                                    </td>
                                    <td data-title="Total">
                                        <span class="amount"><bdi><span>$</span>80.00</bdi></span>
                                    </td>
                                    <td data-title="stock">
                                        <a href="#" class="stock_out">out of stock</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" class:show={tab === 'addresses'} class:active={tab === 'addresses'} id="v-pills-addresses" role="tabpanel"
            aria-labelledby="v-pills-addresses-tab" tabindex="0">
            <AddressSection onchange={loadDefaultAddress} />
        </div>

        <div class="tab-pane fade" class:show={tab === 'plan'} class:active={tab === 'plan'} id="v-pills-plan" role="tabpanel"
            aria-labelledby="v-pills-plan-tab" tabindex="0">
            <PlanLimits />
        </div>

        <div class="tab-pane fade" class:show={tab === 'settings'} class:active={tab === 'settings'} id="v-pills-settings"
            role="tabpanel" aria-labelledby="v-pills-settings-tab" tabindex="0">
            <Account onnavigatetoaddresses={() => (tab = 'addresses')} />
        </div>

        <div class="tab-pane fade" class:show={tab === 'logout'} class:active={tab === 'logout'} id="v-pills-logout" role="tabpanel"
            aria-labelledby="v-pills-logout-tab" tabindex="0">
            <div class="card border p-4 text-center">
                <div class="mb-3">
                    <i class="fa-solid fa-arrow-right-from-bracket fs-1 text-danger"></i>
                </div>
                <h4>{$translate('Log Out')}</h4>
                <p class="text-muted">Are you sure you want to log out of your account?</p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" class="theme-btn" onclick={async () => { await logout(); goto('/login'); }}>
                        Confirm Log Out
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-4" onclick={() => (tab = 'dashboard')}>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>  
</div>
</div>
</div>
</div>

<style>
	:global(.account-dashboard) {
		background:
			linear-gradient(180deg, #f8fbff 0%, #ffffff 44%),
			#fff;
	}

	:global(.account-dashboard-grid) {
		align-items: flex-start;
	}

	:global(.dashboard-navigation-sidebar) {
		position: sticky;
		top: 18px;
		padding: 18px;
		border: 1px solid #e7edf5;
		border-radius: 22px;
		background: #ffffff;
		box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
	}

	:global(.dashboard-navigation-sidebar h3) {
		margin: 0 0 16px;
		color: #0f172a;
		font-size: 15px;
		font-weight: 900;
		letter-spacing: 0;
	}

	:global(.dashboard-navigation-sidebar .nav) {
		gap: 8px;
	}

	:global(.dashboard-navigation-sidebar .nav-link) {
		display: flex;
		align-items: center;
		gap: 10px;
		width: 100%;
		min-height: 46px;
		padding: 0 14px;
		border: 1px solid transparent;
		border-radius: 14px;
		background: transparent;
		color: #475569;
		font-size: 14px;
		font-weight: 800;
		text-align: left;
		transition: background-color 0.18s ease, color 0.18s ease, border-color 0.18s ease;
	}

	:global(.dashboard-navigation-sidebar .nav-link i) {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 28px;
		height: 28px;
		border-radius: 10px;
		background: #f1f5f9;
		color: #64748b;
		font-size: 13px;
		flex: 0 0 auto;
	}

	:global(.dashboard-navigation-sidebar .nav-link:hover) {
		border-color: #dbe6f2;
		background: #f8fafc;
		color: #0f172a;
	}

	:global(.dashboard-navigation-sidebar .nav-link.active) {
		border-color: color-mix(in srgb, var(--theme) 22%, #dbe6f2);
		border-left-color: color-mix(in srgb, var(--theme) 22%, #dbe6f2);
		background: color-mix(in srgb, var(--theme) 10%, #fff);
		color: #0f172a;
	}

	:global(.dashboard-navigation-sidebar .nav-link::before),
	:global(.dashboard-navigation-sidebar .nav-link::after) {
		display: none;
	}

	:global(.dashboard-navigation-sidebar .nav-link.active i) {
		background: var(--theme);
		color: #fff;
	}

	:global(.dashboard-wrapper),
	:global(.tab-pane > .card),
	:global(.wishlist-wrapper),
	:global(.dashboard-section .address-section),
	:global(.dashboard-section .chat-wrapper) {
		border: 1px solid #e7edf5;
		border-radius: 24px;
		background: #ffffff;
		box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
		overflow: hidden;
	}

	:global(.dashboard-wrapper) {
		padding: 18px;
	}

	:global(.dashboard-top .dashboard-profile),
	:global(.dashboard-top .dashboard-profile-info) {
		height: 100%;
		min-height: 250px;
		display: flex;
		flex-direction: column;
		justify-content: center;
		border: 1px solid #e8eef6;
		border-radius: 20px;
		background: #f8fafc;
	}

	:global(.dashboard-top .dashboard-profile) {
		background:
			radial-gradient(circle at 88% 8%, color-mix(in srgb, var(--theme) 12%, transparent), transparent 32%),
			#f8fafc;
	}

	:global(.dashboard-profile h3),
	:global(.dashboard-profile-info h5) {
		color: #0f172a;
		font-weight: 900;
	}

	:global(.dashboard-profile p),
	:global(.dashboard-profile-info p),
	:global(.dashboard-profile-info .address),
	:global(.dashboard-profile-info .phone) {
		color: #64748b;
	}

	:global(.dashboard-profile a),
	:global(.dashboard-profile-info .edit) {
		color: var(--theme);
		font-weight: 900;
	}

	:global(.order-history) {
		margin-top: 18px;
		padding: 18px;
		border: 1px solid #e8eef6;
		border-radius: 20px;
		background: #fff;
	}

	:global(.order-history .header) {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 12px;
	}

	:global(.order-history .header h2) {
		margin: 0;
		color: #0f172a;
		font-size: 20px;
		font-weight: 900;
	}

	:global(.order-history .view-all) {
		color: var(--theme);
		font-size: 13px;
		font-weight: 900;
	}

	:global(.dashboard-top .dashboard-profile-info .address) {
		max-width: 100% !important;
	}

	.avatar-thumb {
		position: relative;
		width: 120px;
		height: 120px;
		margin: 0 auto 16px auto;
	}

	.avatar-img {
		width: 120px;
		height: 120px;
		border-radius: 50%;
		object-fit: cover;
		display: block;
		border: 3px solid #fff;
		box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
	}

	.avatar-camera-btn {
		position: absolute;
		bottom: 0;
		right: 0;
		width: 34px;
		height: 34px;
		border-radius: 50%;
		background-color: var(--theme, #ed0006);
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 14px;
		cursor: pointer;
		box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
		transition: transform 0.2s ease, background-color 0.2s ease;
		z-index: 2;
	}

	.avatar-camera-btn:hover {
		transform: scale(1.08);
		background-color: #c90005;
	}

	.avatar-loading-overlay {
		position: absolute;
		top: 0;
		left: 0;
		width: 120px;
		height: 120px;
		border-radius: 50%;
		background: rgba(0, 0, 0, 0.5);
		color: #fff;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 24px;
		z-index: 3;
	}

	@media (max-width: 1199.98px) {
		:global(.dashboard-section) {
			padding-top: 28px;
			padding-bottom: 34px;
		}

		:global(.dashboard-section .row) {
			row-gap: 18px;
		}

		:global(.dashboard-navigation-sidebar) {
			position: static;
			margin-bottom: 0;
			padding: 14px;
			border-radius: 20px;
		}

		:global(.dashboard-navigation-sidebar h3) {
			margin: 0 0 10px;
			color: #0f172a;
			font-size: 14px;
			font-weight: 800;
			line-height: 1.2;
		}

		:global(.dashboard-navigation-sidebar .nav) {
			display: grid !important;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 8px;
			overflow: visible;
			padding: 0;
		}

		:global(.dashboard-navigation-sidebar .nav::-webkit-scrollbar) {
			display: none;
		}

		:global(.dashboard-navigation-sidebar .nav-link) {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			min-width: 0;
			min-height: 42px;
			padding: 0 14px;
			border: 1px solid #e7edf5;
			border-radius: 999px;
			background: #f8fafc;
			color: #334155;
			font-size: 13px;
			font-weight: 800;
			line-height: 1;
			white-space: nowrap;
		}

		:global(.dashboard-navigation-sidebar .nav-link i) {
			margin-right: 7px;
			width: auto;
			height: auto;
			border-radius: 0;
			background: transparent;
			color: #94a3b8;
			font-size: 13px;
		}

		:global(.dashboard-navigation-sidebar .nav-link.active) {
			border-color: var(--theme);
			border-left-color: var(--theme);
			background: var(--theme);
			color: #fff;
			box-shadow: 0 10px 24px rgba(22, 163, 74, 0.22);
		}

		:global(.dashboard-navigation-sidebar .nav-link.active i) {
			color: #fff;
		}

		:global(.dashboard-wrapper),
		:global(.tab-content > .tab-pane > .card),
		:global(.order-history),
		:global(.dashboard-profile),
		:global(.dashboard-profile-info) {
			border-radius: 18px;
		}

		:global(.dashboard-top .dashboard-profile),
		:global(.dashboard-top .dashboard-profile-info) {
			min-height: auto;
			padding: 24px 18px;
		}
	}

	@media (max-width: 575.98px) {
		:global(.account-dashboard) {
			background: #f4f8fc;
		}

		:global(.dashboard-section) {
			padding-top: 18px;
			padding-bottom: 28px;
		}

		:global(.dashboard-section .container) {
			padding-left: 14px;
			padding-right: 14px;
		}

		:global(.dashboard-navigation-sidebar) {
			margin: 0 -2px 2px;
			padding: 8px;
			border-radius: 18px;
			box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
		}

		:global(.dashboard-navigation-sidebar h3) {
			display: none;
		}

		:global(.dashboard-navigation-sidebar .nav-link) {
			flex: 0 0 auto;
			width: auto;
			min-width: max-content;
			min-height: 38px;
			padding: 0 12px;
			font-size: 12px;
		}

		:global(.dashboard-navigation-sidebar .nav) {
			display: flex !important;
			flex-direction: row !important;
			flex-wrap: nowrap;
			gap: 8px;
			overflow-x: auto;
			padding: 0 2px 2px;
			scrollbar-width: none;
			-webkit-overflow-scrolling: touch;
		}

		:global(.dashboard-navigation-sidebar .nav-link i) {
			margin-right: 6px;
		}

		:global(.dashboard-wrapper) {
			padding: 10px;
			border-radius: 20px;
		}

		:global(.dashboard-top .row) {
			--bs-gutter-y: 10px;
		}

		:global(.dashboard-top .dashboard-profile),
		:global(.dashboard-top .dashboard-profile-info) {
			align-items: flex-start !important;
			padding: 18px;
			text-align: left;
		}

		:global(.dashboard-top .dashboard-profile) {
			position: relative;
			display: grid !important;
			grid-template-columns: auto minmax(0, 1fr);
			column-gap: 14px;
			align-items: center !important;
			justify-content: flex-start !important;
		}

		.avatar-thumb,
		.avatar-img,
		.avatar-loading-overlay {
			width: 68px;
			height: 68px;
		}

		.avatar-thumb {
			grid-row: span 4;
			margin: 0;
		}

		.avatar-camera-btn {
			width: 26px;
			height: 26px;
			font-size: 11px;
		}

		:global(.dashboard-profile h3) {
			margin: 0 0 4px;
			font-size: 19px;
			line-height: 1.15;
		}

		:global(.dashboard-profile p) {
			margin: 0 0 8px;
			font-size: 13px;
			line-height: 1.35;
		}

		:global(.dashboard-profile a) {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			min-height: 34px;
			padding: 0 12px;
			border-radius: 999px;
			background: color-mix(in srgb, var(--theme) 10%, #fff);
			font-size: 12px;
		}

		:global(.dashboard-profile-info h6) {
			margin: 0 0 8px;
			color: #64748b;
			font-size: 12px;
			font-weight: 900;
			text-transform: uppercase;
		}

		:global(.dashboard-profile-info h5) {
			margin-bottom: 6px;
			font-size: 17px;
		}

		:global(.dashboard-profile-info .edit) {
			display: inline-flex;
			align-items: center;
			min-height: 34px;
			margin-top: 8px;
			padding: 0 12px !important;
			border-radius: 999px;
			background: color-mix(in srgb, var(--theme) 10%, #fff) !important;
			font-size: 12px;
		}

		:global(.order-history) {
			margin-top: 10px;
			padding: 14px;
			border-radius: 18px;
		}

		:global(.order-history .header h2) {
			font-size: 17px;
		}

		:global(.dashboard-profile p),
		:global(.dashboard-profile-info p),
		:global(.dashboard-profile-info a) {
			overflow-wrap: anywhere;
		}

		:global(.order-history) {
			overflow-x: auto;
			-webkit-overflow-scrolling: touch;
		}
	}
</style>
