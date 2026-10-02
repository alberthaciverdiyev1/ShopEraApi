<script lang="ts">
	import type { ApiPromoBlock } from '$lib/services/promoBlocks';
	import { translate } from '$lib/i18n';

	let { blocks = [] }: { blocks?: ApiPromoBlock[] } = $props();

	const offers = $derived(blocks.filter((b) => b.type === 'offer'));
	const ads = $derived(blocks.filter((b) => b.type === 'ad'));
</script>

{#if offers.length > 0}
	<section class="promo-offer-section">
		<div class="container">
			<div class="promo-offer-grid">
				{#each offers as block (block.id)}
					<div class="promo-offer-card">
						{#if block.image}
							<img src={block.image} alt={block.title ?? 'Offer'} />
						{:else}
							<div class="promo-offer-ph"></div>
						{/if}
						<div class="promo-offer-body">
							{#if block.badge}<span class="promo-badge">{block.badge}</span>{/if}
							{#if block.title}<h3>{block.title}</h3>{/if}
							{#if block.subtitle}<p class="promo-sub">{block.subtitle}</p>{/if}
							{#if block.description}<p class="promo-desc">{block.description}</p>{/if}
							{#if block.url}
								<a class="promo-cta" href={block.url}>{block.button_text || $translate('Buy now')}</a>
							{/if}
						</div>
					</div>
				{/each}
			</div>
		</div>
	</section>
{/if}

{#if ads.length > 0}
	<section class="promo-ad-section">
		<div class="container">
			{#each ads as block (block.id)}
				<div class="promo-ad">
					{#if block.image}<img src={block.image} alt={block.title ?? 'Ad'} />{/if}
					<div class="promo-ad-body">
						{#if block.badge}<span class="promo-badge alt">{block.badge}</span>{/if}
						{#if block.title}<h3>{block.title}</h3>{/if}
						{#if block.subtitle}<p class="promo-sub">{block.subtitle}</p>{/if}
						{#if block.description}<p class="promo-desc">{block.description}</p>{/if}
						{#if block.url}<a class="promo-cta" href={block.url}>{block.button_text || $translate('Details')}</a>{/if}
					</div>
				</div>
			{/each}
		</div>
	</section>
{/if}

<style>
	.promo-offer-section { padding: 28px 0; }
	.promo-offer-grid { display: grid; grid-template-columns: 1fr; gap: 18px; }
	@media (min-width: 768px) { .promo-offer-grid { grid-template-columns: repeat(2, 1fr); } }
	.promo-offer-card {
		display: flex; gap: 16px; background: #fff; border: 1px solid #eef1f5;
		border-radius: 18px; overflow: hidden; box-shadow: 0 12px 30px rgba(15,23,42,.06);
	}
	.promo-offer-card img { width: 180px; height: 100%; min-height: 160px; object-fit: cover; }
	.promo-offer-ph { width: 180px; min-height: 160px; background: linear-gradient(135deg,#eef2ff,#e0f2fe); }
	.promo-offer-body { padding: 18px; display: flex; flex-direction: column; gap: 6px; }
	.promo-badge {
		align-self: flex-start; background: #e11d48; color: #fff; font-size: 12px; font-weight: 800;
		padding: 3px 10px; border-radius: 999px; letter-spacing: .02em;
	}
	.promo-badge.alt { background: #0ea5e9; }
	.promo-offer-body h3, .promo-ad-body h3 { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; }
	.promo-sub { margin: 0; font-size: 14px; font-weight: 600; color: #475569; }
	.promo-desc { margin: 0; font-size: 13px; color: #64748b; }
	.promo-cta {
		margin-top: 6px; align-self: flex-start; background: #0f172a; color: #fff; font-size: 13px;
		font-weight: 700; padding: 9px 18px; border-radius: 10px; text-decoration: none;
	}
	.promo-cta:hover { background: #1e293b; }

	.promo-ad-section { padding: 10px 0 28px; }
	.promo-ad {
		display: flex; align-items: center; gap: 18px; background: linear-gradient(120deg,#0ea5e9,#6366f1);
		color: #fff; border-radius: 18px; overflow: hidden;
	}
	.promo-ad img { width: 200px; height: 140px; object-fit: cover; }
	.promo-ad-body { padding: 18px; display: flex; flex-direction: column; gap: 6px; }
	.promo-ad-body h3 { color: #fff; }
	.promo-ad .promo-sub, .promo-ad .promo-desc { color: rgba(255,255,255,.85); }
	.promo-ad .promo-cta { background: #fff; color: #0f172a; }
</style>
