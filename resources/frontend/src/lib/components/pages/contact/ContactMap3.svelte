<script lang="ts">
	import type { ApiContactInfo } from '$lib/services/contact';

	let { contactInfo }: { contactInfo?: ApiContactInfo } = $props();

	const mapSrc = $derived.by(() => {
		const address = contactInfo?.address?.trim() || 'Bakı, Azərbaycan';
		return `https://maps.google.com/maps?q=${encodeURIComponent(address)}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
	});
</script>

<!-- contact-map Section START -->
<div class="contact-map-section fix">
	<div class="map-items">
		<div class="googpemap">
			<iframe
				src={mapSrc}
				title="Google Maps"
				style="border:0;"
				allowfullscreen
				loading="lazy"
			></iframe>
		</div>
	</div>
</div>
<!-- contact-map Section END -->
