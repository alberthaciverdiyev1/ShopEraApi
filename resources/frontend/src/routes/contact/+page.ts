import { fetchContactInfo } from '$lib/services/contact';

export const load = async ({ fetch }) => {
	const contactInfo = await fetchContactInfo(fetch);

	return {
		contactInfo
	};
};
