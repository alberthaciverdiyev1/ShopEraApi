<script lang="ts">
	import { translate } from '$lib/i18n';
	import { onMount } from 'svelte';
	import { fetchFaqs, localized, type ApiFaq } from '$lib/services/content';

	let faqs = $state<ApiFaq[]>([]);
	let loading = $state(true);
	let openId = $state<number | null>(null);

	onMount(async () => {
		try {
			faqs = await fetchFaqs();
			openId = faqs[0]?.id ?? null;
		} catch {
			faqs = [];
		} finally {
			loading = false;
		}
	});
</script>

<!-- FAQ (API) -->
<section class="faq-section section-padding fix">
    <div class="container">
        <div class="faq-wrapper">
            <div class="section-title text-center mb-4">
                <div class="subtitle style1">Help center</div>
                <h2 class="title">Frequently asked questions</h2>
            </div>

            {#if loading}
                <p class="text-center">{$translate('Loading…')}</p>
            {:else if faqs.length === 0}
                <p class="text-center">No questions yet.</p>
            {:else}
                <div class="faq-accordion mt-4 mt-md-0">
                    <div class="accordion">
                        {#each faqs as faq (faq.id)}
                            <div class="accordion-item mb-3">
                                <h5 class="accordion-header">
                                    <button class="accordion-button" class:collapsed={openId !== faq.id}
                                            type="button"
                                            aria-expanded={openId === faq.id}
                                            onclick={() => (openId = openId === faq.id ? null : faq.id)}>
                                        {localized(faq.title)}
                                    </button>
                                </h5>
                                {#if openId === faq.id}
                                    <div class="accordion-collapse collapse show">
                                        <div class="accordion-body">
                                            <p>{localized(faq.description)}</p>
                                        </div>
                                    </div>
                                {/if}
                            </div>
                        {/each}
                    </div>
                </div>
            {/if}
        </div>
    </div>
</section>
