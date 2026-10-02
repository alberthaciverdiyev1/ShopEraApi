import { fetchLegalTerm, localized } from '$lib/services/content';
import { fetchFeaturedReviews } from '$lib/services/reviews';
import { fetchFeatures, type ApiFeatures } from '$lib/services/features';

export const load = async ({ fetch }) => {
	const [term, featuredReviews, features] = await Promise.all([
		fetchLegalTerm('about').catch(() => null),
		fetchFeaturedReviews(fetch).catch(() => []),
		fetchFeatures(fetch).catch((): ApiFeatures => ({}))
	]);

	return {
		html: term ? localized(term.html) : '',
		featuredReviews,
		features
	};
};
