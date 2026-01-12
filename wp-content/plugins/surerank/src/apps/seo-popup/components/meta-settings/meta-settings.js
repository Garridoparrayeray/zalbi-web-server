import { Fragment, memo, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { motion } from 'framer-motion';
import {
	withSelect,
	withDispatch,
	useDispatch,
	useSelect,
} from '@wordpress/data';
import { compose } from '@wordpress/compose';
import { STORE_NAME } from '@Store/constants';
import {
	GutenbergData,
	ClassicEditorData,
} from '@/apps/seo-popup/modal/dynamic-data-provider';
import { MetaTab, SocialTab } from './tab-content';
import LoadingSkeleton from '../loading-skeleton';
import { Tabs } from '@bsf/force-ui';

const tabs = [
	{
		label: __( 'Meta', 'surerank' ),
		slug: 'meta',
	},
	{
		label: __( 'Social', 'surerank' ),
		slug: 'social',
	},
];

const MetaSettings = ( props ) => {
	const { postMetaData, updatePostMetaData, initialized, globalDefaults } =
		props;

	const { updateAppSettings } = useDispatch( STORE_NAME );
	const { currentMetaTab } = useSelect( ( select ) =>
		select( STORE_NAME ).getAppSettings()
	);

	const [ activeTab, setActiveTab ] = useState( currentMetaTab || 'meta' );

	useEffect( () => {
		if ( currentMetaTab && currentMetaTab !== activeTab ) {
			setActiveTab( currentMetaTab );
		}
	}, [ currentMetaTab ] );

	const handleChangeTab = ( { event, value: { slug } } ) => {
		event.preventDefault();
		event.stopPropagation();

		setActiveTab( slug );
		updateAppSettings( { currentMetaTab: slug } );
	};

	let tabContent = null;
	switch ( activeTab ) {
		case 'meta':
			tabContent = (
				<MetaTab
					postMetaData={ postMetaData }
					updatePostMetaData={ updatePostMetaData }
					globalDefaults={ globalDefaults }
				/>
			);
			break;
		case 'social':
			tabContent = (
				<SocialTab
					postMetaData={ postMetaData }
					updatePostMetaData={ updatePostMetaData }
					globalDefaults={ globalDefaults }
				/>
			);
			break;
		default:
			tabContent = null;
	}

	if ( ! initialized ) {
		tabContent = <LoadingSkeleton tab={ activeTab } />;
	}

	return (
		<Fragment>
			<div>
				<Tabs.Group
					className="w-full"
					size="md"
					variant="rounded"
					activeItem={ activeTab }
					onChange={ handleChangeTab }
				>
					{ tabs.map( ( { label, slug } ) => (
						<Tabs.Tab
							key={ label }
							slug={ slug }
							text={ label }
							className="text-sm"
						/>
					) ) }
				</Tabs.Group>
			</div>

			{ /* Tab content */ }
			<motion.div
				key={ activeTab }
				className="flex flex-col gap-2 flex-1 overflow-y-auto"
				initial={ { opacity: 0 } }
				animate={ { opacity: 1 } }
				exit={ { opacity: 0 } }
				transition={ { duration: 0.2 } }
			>
				{ tabContent }
			</motion.div>
		</Fragment>
	);
};

let hocComponent = ( Component ) => Component;
if ( 'block' === surerank_seo_popup?.editor_type ) {
	hocComponent = GutenbergData;
} else if ( 'classic' === surerank_seo_popup?.editor_type ) {
	hocComponent = ClassicEditorData;
}

export default compose(
	withSelect( ( select ) => {
		const selectStore = select( STORE_NAME );

		return {
			postMetaData: selectStore.getPostSeoMeta(),
			initialized: selectStore.getMetaboxState(),
			globalDefaults: selectStore.getGlobalDefaults(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		const dispatchStore = dispatch( STORE_NAME );

		return {
			updatePostMetaData: ( value ) =>
				dispatchStore.updatePostMetaData( value ),
		};
	} ),
	hocComponent
)( memo( MetaSettings ) );
