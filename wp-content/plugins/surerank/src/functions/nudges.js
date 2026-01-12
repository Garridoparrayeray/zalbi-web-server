/**
 * Get the pricing link for the SureRank plugin.
 *
 * @param {string} utm_medium - The UTM parameter to include in the link.
 * @return {string} The pricing link URL.
 */
export const getPricingLink = ( utm_medium = 'surerank_plugin' ) => {
	return (
		window?.surerank_globals?.pricing_link + `?utm_medium=${ utm_medium }`
	);
};

/**
 * Redirect to the pricing page for the SureRank plugin.
 *
 * @param {string} utm_medium - The UTM parameter to include in the link.
 */
export const redirectToPricingPage = ( utm_medium ) => {
	const pricingLink = getPricingLink( utm_medium );
	window.open( pricingLink, '_blank', 'noopener,noreferrer' );
};

export const isProActive = () => {
	return window?.surerank_globals?.is_pro_active;
};
