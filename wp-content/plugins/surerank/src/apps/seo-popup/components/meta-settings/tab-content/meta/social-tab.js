import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { motion } from 'framer-motion';
import {
	Accordion,
	Label,
	Input,
	EditorInput,
	Switch,
	Button,
} from '@bsf/force-ui';
import { useCallback, useRef, useState } from '@wordpress/element';
import { Facebook, Info, RefreshCcw } from 'lucide-react';
import SocialPreview from '@GlobalComponents/social-preview';
import { STORE_NAME } from '@Store/constants';
import {
	INPUT_VARIABLE_SUGGESTIONS as variableSuggestions,
	MAX_EDITOR_INPUT_LENGTH,
} from '@Global/constants';
import {
	editorValueToString,
	stringValueToFormatJSON,
	truncateText,
} from '@Functions/utils';
import replacement from '@Functions/replacement';
import { flat } from '@Functions/variables';
import { SeoPopupTooltip } from '@AdminComponents/tooltip';
import { XSocialIcon } from '@/global/components/icons';
import MediaPreview from '@/apps/admin-components/media-preview';
import MagicButton from '@/apps/seo-popup/components/fix-it-for-me/magic-button';
import { createMediaFrame } from '@/global/utils/utils';

const socialMedia = [
	{
		label: 'Facebook',
		icon: Facebook,
		tabSlug: 'facebook',
	},
	{
		label: 'X',
		icon: XSocialIcon,
		tabSlug: 'twitter',
	},
];

const renderIf = ( condition, content, fallbackContent ) => {
	if ( condition ) {
		return typeof content === 'function' ? content() : content;
	}

	return typeof fallbackContent === 'function'
		? fallbackContent()
		: fallbackContent;
};

const SocialTab = ( { postMetaData, updatePostMetaData, globalDefaults } ) => {
	const { variables, postDynamicData } = useSelect( ( select ) => {
		const { getVariables, getPostDynamicData } = select( STORE_NAME );
		return {
			variables: getVariables(),
			postDynamicData: getPostDynamicData(),
		};
	}, [] );

	const defaultGlobalMeta = globalDefaults;

	const [ activeTab, setActiveTab ] = useState( 'facebook' );

	const getPreviewData = useCallback(
		( key, fallbackValue ) => {
			let mainKey = activeTab;

			if (
				'twitter' === activeTab &&
				!! postMetaData?.twitter_same_as_facebook
			) {
				mainKey = 'facebook';
			}

			return (
				postMetaData?.[ `${ mainKey }_${ key }` ] ||
				fallbackValue?.[ `${ mainKey }_${ key }` ]
			);
		},
		[ activeTab, postMetaData, variables, postDynamicData ]
	);

	const variablesArray = flat( variables );
	const titlePreview = truncateText(
		replacement(
			getPreviewData( 'title', defaultGlobalMeta ),
			variablesArray,
			postDynamicData
		),
		null
	);
	const descriptionPreview = truncateText(
		replacement(
			getPreviewData( 'description', defaultGlobalMeta ),
			variablesArray,
			postDynamicData
		),
		78
	);

	const fallbackImage = postMetaData?.auto_generated_og_image
		? postMetaData?.auto_generated_og_image
		: defaultGlobalMeta?.fallback_image;

	const defaultGlobalImage = getPreviewData( 'image_url', defaultGlobalMeta )
		? getPreviewData( 'image_url', defaultGlobalMeta )
		: fallbackImage;

	let finalFallbackImage = postMetaData?.[ `${ activeTab }_image_url` ]
		? postMetaData?.[ `${ activeTab }_image_url` ]
		: defaultGlobalImage;

	if ( activeTab === 'twitter' ) {
		if ( postMetaData?.twitter_same_as_facebook ) {
			finalFallbackImage =
				postMetaData?.facebook_image_url || fallbackImage;
		}
	}

	const hasImageSelected = !! postMetaData?.[ `${ activeTab }_image_url` ];

	const handleAccordionChange = ( value ) => {
		setActiveTab( value === 'twitter' ? 'twitter' : 'facebook' );
	};

	const handleRemoveImage = () => {
		updatePostMetaData( {
			[ `${ activeTab }_image_url` ]: '',
			[ `${ activeTab }_image_id` ]: '',
		} );
	};

	// Editor refs.
	const titleEditor = useRef( null );
	const descriptionEditor = useRef( null );

	const handleUpdatePostMetaData = ( key, value ) => {
		// if value is same as previous value, return
		if ( postMetaData[ key ] === value ) {
			return;
		}
		updatePostMetaData( {
			[ key ]: value,
		} );
	};

	const handleClickInput = ( event ) => {
		event.preventDefault();

		const mediaUploader = createMediaFrame( {
			title: 'Select Image',
			button: {
				text: 'Use this image',
			},
			multiple: false,
		} );

		mediaUploader.on( 'select', () => {
			const attachment = mediaUploader
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			updatePostMetaData( {
				[ `${ activeTab }_image_url` ]: attachment.url,
				[ `${ activeTab }_image_id` ]: attachment.id,
			} );
		} );

		mediaUploader.open();
	};

	const handleClearFacebookCache = () => {
		const url =
			window?.wp?.data?.select( 'core/editor' )?.getPermalink() ||
			variables?.term?.permalink?.value;

		window.open(
			`https://developers.facebook.com/tools/debug/?q=${ url }`,
			'_blank'
		);
	};

	return (
		<div className="flex flex-col gap-2 max-h-full w-full">
			<Accordion
				autoClose={ true }
				defaultValue="facebook"
				collapsible={ false }
				type="boxed"
				className="bg-background-secondary space-y-1 rounded-lg w-full"
			>
				{ socialMedia.map( ( { label, tabSlug } ) => (
					<Accordion.Item
						key={ tabSlug }
						value={ tabSlug }
						className="bg-background-primary border-none w-full" /* Added min-height */
					>
						<Accordion.Trigger
							className="text-base [&>svg]:size-5 pr-2 pl-3 py-3 flex items-center gap-2 w-full"
							onClick={ () => handleAccordionChange( tabSlug ) }
						>
							{ label }
						</Accordion.Trigger>
						<Accordion.Content>
							<motion.div
								key={ tabSlug }
								className="flex-1 flex flex-col gap-2 overflow-y-auto"
								initial={ { opacity: 0 } }
								animate={ { opacity: 1 } }
								exit={ { opacity: 0 } }
								transition={ { duration: 0.2 } }
							>
								{ /* Use data from Facebook tab toggle button */ }
								{ tabSlug === 'twitter' && (
									<div className="flex items-center gap-3 p-2">
										<Switch
											id="facebook_same_as_twitter"
											name="facebook_same_as_twitter"
											size="sm"
											defaultValue={
												!! postMetaData?.twitter_same_as_facebook
											}
											onChange={ ( value ) => {
												handleUpdatePostMetaData(
													'twitter_same_as_facebook',
													value ? '1' : false
												);
											} }
										/>
										<Label
											htmlFor="facebook_same_as_twitter"
											size="sm"
										>
											{ __(
												'Use Data from Facebook Tab',
												'surerank'
											) }
										</Label>
									</div>
								) }

								{ renderIf(
									tabSlug === 'twitter' &&
										!! postMetaData?.twitter_same_as_facebook,
									null,
									<>
										<div className="p-2 space-y-1.5">
											{ /* Label */ }
											<div className="flex items-center gap-1">
												<Label tag="span" size="sm">
													{ __(
														'Social Image',
														'surerank'
													) }
												</Label>
												<SeoPopupTooltip
													content={ __(
														'Upload at least 600x315px image. Recommended size is 1200x630px.',
														'surerank'
													) }
													placement="top"
													arrow
													className="z-[99999]"
												>
													<Info className="size-4 text-icon-secondary" />
												</SeoPopupTooltip>
											</div>

											{ /* Input */ }
											<Input
												className="m-0 [&>input]:m-0 [&>input]:transition-colors [&>input]:duration-150 [&>input]:ease-in-out"
												type="file"
												size="md"
												onClick={ handleClickInput }
											/>

											{ hasImageSelected && (
												<MediaPreview
													imageId={
														postMetaData?.[
															`${ activeTab }_image_id`
														]
													}
													onRemove={
														handleRemoveImage
													}
												/>
											) }
										</div>
										{ /* Social Title */ }
										<div className="space-y-1.5 p-2">
											{ /* Label & Limit */ }
											<div className="flex items-center justify-between gap-1">
												<Label
													tag="span"
													size="sm"
													className="space-x-0.5"
												>
													<span>
														{ __(
															'Social Title',
															'surerank'
														) }
													</span>
												</Label>
												<MagicButton
													fieldKey={ `${ tabSlug }_title` }
													onUseThis={ (
														fieldKey,
														content
													) => {
														handleUpdatePostMetaData(
															fieldKey,
															content
														);
													} }
													tooltip={ __(
														'Generate with AI',
														'surerank'
													) }
												/>
											</div>
											{ /* Input */ }
											<EditorInput
												ref={ titleEditor }
												by="label"
												defaultValue={ stringValueToFormatJSON(
													postMetaData?.[
														`${ tabSlug }_title`
													],
													variableSuggestions,
													'value'
												) }
												trigger="@"
												options={ variableSuggestions }
												onChange={ ( editorState ) => {
													handleUpdatePostMetaData(
														`${ tabSlug }_title`,
														editorValueToString(
															editorState.toJSON()
														)
													);
												} }
												placeholder={ '' }
											/>
											{ /* Hint text */ }
											<span className="block text-xs leading-4 font-normal text-field-helper">
												{ __(
													'Type @ to view variable suggestions',
													'surerank'
												) }
											</span>
										</div>

										{ /* Social description */ }
										<div className="space-y-1.5 p-2">
											{ /* Label & Limit */ }
											<div className="flex items-center justify-between gap-1">
												<Label
													tag="span"
													size="sm"
													className="space-x-0.5"
												>
													<span>
														{ __(
															'Social Description',
															'surerank'
														) }
													</span>
												</Label>
												<MagicButton
													fieldKey={ `${ tabSlug }_description` }
													onUseThis={ (
														fieldKey,
														content
													) => {
														handleUpdatePostMetaData(
															fieldKey,
															content
														);
													} }
													tooltip={ __(
														'Generate with AI',
														'surerank'
													) }
												/>
											</div>
											{ /* Input */ }
											<EditorInput
												ref={ descriptionEditor }
												className="[&+div]:items-start [&+div]:pt-1"
												by="label"
												defaultValue={ stringValueToFormatJSON(
													postMetaData?.[
														`${ tabSlug }_description`
													],
													variableSuggestions,
													'value'
												) }
												options={ variableSuggestions }
												onChange={ ( editorState ) => {
													handleUpdatePostMetaData(
														`${ tabSlug }_description`,
														editorValueToString(
															editorState.toJSON()
														)
													);
												} }
												trigger="@"
												placeholder={ '' }
												maxLength={
													MAX_EDITOR_INPUT_LENGTH
												}
											/>
											{ /* Hint text */ }
											<span className="block text-xs leading-4 font-normal text-field-helper">
												{ __(
													'Type @ to view variable suggestions',
													'surerank'
												) }
											</span>
										</div>
									</>
								) }
								<div className="p-2 space-y-2">
									{ /* Label */ }
									<div className="flex items-center justify-between">
										<Label tag="span" size="sm">
											{ sprintf(
												// Translators: %s: Facebook or Twitter
												__( '%s Preview', 'surerank' ),
												tabSlug === 'facebook'
													? 'Facebook'
													: 'X'
											) }
										</Label>
										{ tabSlug === 'facebook' && (
											<SeoPopupTooltip
												content={ __(
													"Click to update Facebook's share preview cache. This will update the preview with the latest content.",
													'surerank'
												) }
												placement="top-end"
												offset={ {
													alignmentAxis: '0',
													mainAxis: '8',
												} }
												arrow
											>
												<Button
													size="sm"
													className="p-0.5"
													onClick={
														handleClearFacebookCache
													}
													icon={ <RefreshCcw /> }
													variant="ghost"
												/>
											</SeoPopupTooltip>
										) }
									</div>
									{ /* Preview */ }
									<SocialPreview
										type={ tabSlug }
										title={ titlePreview }
										description={ descriptionPreview }
										imageURL={ finalFallbackImage }
										twitterLargePreview={
											tabSlug === 'twitter' &&
											globalDefaults?.twitter_card_type ===
												'summary_large_image'
										}
										siteURL={ variables?.site?.site_url?.value?.replace(
											/(^\w+:|^)\/\//,
											''
										) }
										hideRemoveButton={ true }
										forMetaBox
									/>
								</div>
							</motion.div>
						</Accordion.Content>
					</Accordion.Item>
				) ) }
			</Accordion>
		</div>
	);
};

export default SocialTab;
