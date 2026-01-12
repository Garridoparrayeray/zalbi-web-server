/**
 * Calculate page check status and counts from categorized checks
 *
 * @param {Object} categorizedChecks - The categorized checks object (should be pre-filtered)
 * @return {Object} Object containing status and counts
 */
export const calculateCheckStatus = ( categorizedChecks = {} ) => {
	// Use pre-filtered checks directly (filtering should happen at store level)
	const badChecks = categorizedChecks.badChecks || [];
	const fairChecks = categorizedChecks.fairChecks || [];
	const passedChecks = categorizedChecks.passedChecks || [];
	const suggestionChecks = categorizedChecks.suggestionChecks || [];

	// Calculate status
	let status = 'success';
	if ( badChecks.length > 0 ) {
		status = 'error';
	} else if ( fairChecks.length > 0 ) {
		status = 'warning';
	} else if ( suggestionChecks.length > 0 ) {
		status = 'suggestion';
	}

	// Calculate counts
	const counts = {
		errorAndWarnings: badChecks.length + fairChecks.length,
		success: passedChecks.length,
		error: badChecks.length,
		warning: fairChecks.length,
		suggestion: suggestionChecks.length,
	};

	return { status, counts };
};

/**
 * Calculate combined status from page and keyword check statuses
 *
 * @param {Object} pageStatus    - Page check status and counts
 * @param {Object} keywordStatus - Keyword check status and counts
 * @return {Object} Object containing combined status and counts
 */
export const calculateCombinedStatus = ( pageStatus, keywordStatus ) => {
	// Create combined checks arrays
	const combinedChecks = {
		badChecks: [
			...( pageStatus.counts?.error
				? Array( pageStatus.counts.error ).fill( { status: 'error' } )
				: [] ),
			...( keywordStatus.counts?.error
				? Array( keywordStatus.counts.error ).fill( {
						status: 'error',
				  } )
				: [] ),
		],
		fairChecks: [
			...( pageStatus.counts?.warning
				? Array( pageStatus.counts.warning ).fill( {
						status: 'warning',
				  } )
				: [] ),
			...( keywordStatus.counts?.warning
				? Array( keywordStatus.counts.warning ).fill( {
						status: 'warning',
				  } )
				: [] ),
		],
		suggestionChecks: [
			...( pageStatus.counts?.suggestion
				? Array( pageStatus.counts.suggestion ).fill( {
						status: 'suggestion',
				  } )
				: [] ),
			...( keywordStatus.counts?.suggestion
				? Array( keywordStatus.counts.suggestion ).fill( {
						status: 'suggestion',
				  } )
				: [] ),
		],
	};

	// Calculate combined status
	let combinedStatus = 'success';
	if ( combinedChecks.badChecks.length > 0 ) {
		combinedStatus = 'error';
	} else if ( combinedChecks.fairChecks.length > 0 ) {
		combinedStatus = 'warning';
	} else if ( combinedChecks.suggestionChecks.length > 0 ) {
		combinedStatus = 'suggestion';
	}

	// Calculate combined counts
	const combinedCounts = {
		errorAndWarnings:
			combinedChecks.badChecks.length + combinedChecks.fairChecks.length,
		success: combinedChecks.counts?.success || 0,
		error: combinedChecks.badChecks.length,
		warning: combinedChecks.fairChecks.length,
		suggestion: combinedChecks.suggestionChecks.length,
	};

	return { status: combinedStatus, counts: combinedCounts };
};
