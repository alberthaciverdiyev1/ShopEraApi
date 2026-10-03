import { fetchLegalTerm, localized } from '$lib/services/content';
import { fetchFeaturedReviews } from '$lib/services/reviews';

export const load = async ({ fetch }) => {
	const [term, featuredReviews] = await Promise.all([
		fetchLegalTerm('about').catch(() => null),
		fetchFeaturedReviews(fetch).catch(() => [])
	]);

	return {
		html: term ? localized(term.html) : '',
		featuredReviews
	};
};
