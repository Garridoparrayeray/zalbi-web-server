import { select } from '@wordpress/data';
import { STORE_NAME } from './constants';
import { addQueryArgs } from '@wordpress/url';
import {
	fetchFromAPI,
	setPageSeoChecksByIdAndType,
	setCurrentPostIgnoredList,
} from './actions';
import { __ } from '@wordpress/i18n';

export function* getCurrentPostIgnoredList() {
	// Do NOT yield on select here; it's a synchronous read from the store.
	const state = yield select( STORE_NAME ).getState();

	// Try multiple sources for postId (state first, then window globals)
	const postId =
		state.pageSeoChecks?.postId ||
		state.variables?.post?.ID?.value ||
		state.variables?.term?.ID?.value ||
		window?.surerank_seo_popup?.post_id ||
		window?.surerank_seo_popup?.term_id ||
		window?.surerank_globals?.post_id ||
		window?.surerank_globals?.term_id;

	// Determine check type from multiple sources
	const checkType =
		state.pageSeoChecks?.checkType ||
		( window?.surerank_seo_popup?.is_taxonomy === '1'
			? 'taxonomy'
			: 'post' );

	// If we don't yet have a postId or checkType, defer resolution.
	if ( ! postId || ! checkType ) {
		return [];
	}

	// Short-circuit if we already have data for this post in state
	const existingData = state.pageSeoChecks?.ignoredList;
	if ( existingData?.length > 0 ) {
		return existingData;
	}

	try {
		const ignoredChecks = yield fetchFromAPI( {
			path: addQueryArgs( 'surerank/v1/checks/ignore-page-check', {
				post_id: postId,
				check_type: checkType,
			} ),
			method: 'GET',
		} );
		yield setCurrentPostIgnoredList( ignoredChecks?.checks || [] );
	} catch ( error ) {
		// Silently handle errors by setting an empty list
		yield setCurrentPostIgnoredList( [] );
	}
}

export function* getSeoBarChecks( postId, postType, forceRefresh = null ) {
	if ( ! postId || ! postType ) {
		return {};
	}

	const cacheBuster = forceRefresh ? `&_t=${ forceRefresh }` : '';

	const isTaxonomy = window?.surerank_seo_bar?.type === 'taxonomy';
	const apiPath = isTaxonomy
		? addQueryArgs( '/surerank/v1/checks/taxonomy', {
				term_id: postId,
		  } )
		: addQueryArgs( '/surerank/v1/checks/page', { post_id: postId } );

	try {
		const response = yield fetchFromAPI( {
			path: apiPath + cacheBuster,
			method: 'GET',
		} );

		if ( response?.status !== 'success' ) {
			throw new Error(
				response?.message ||
					__( 'Error loading SEO checks', 'surerank' )
			);
		}

		yield setPageSeoChecksByIdAndType(
			postId,
			postType,
			Object.entries( response?.checks ).map( ( [ key, value ] ) => ( {
				...value,
				id: key,
				title:
					value?.message ||
					key
						.replace( /_/g, ' ' )
						.replace( /\b\w/g, ( c ) => c.toUpperCase() ),
				data: value?.description,
				showImages: key === 'image_alt_text',
			} ) )
		);
	} catch ( error ) {
		const errorMessage =
			error?.message || __( 'Error loading SEO checks', 'surerank' );
		yield setPageSeoChecksByIdAndType( postId, postType, [], errorMessage );
	}
}
