import { __ } from '@wordpress/i18n';
import { Accordion } from '@bsf/force-ui';
import GeneralTab from './meta/general-tab';
import AdvancedTab from './meta/advanced-tab';
import SchemaTab from './schema';
import { ENABLE_SCHEMAS } from '@/global/constants';

const MetaTab = ( { postMetaData, updatePostMetaData, globalDefaults } ) => {
	return (
		<Accordion
			autoClose={ true }
			defaultValue="general"
			type="boxed"
			className="bg-background-secondary space-y-1 p-1 rounded-lg"
		>
			<Accordion.Item
				value="general"
				className="bg-background-primary border-none"
			>
				<Accordion.Trigger className="text-base [&>svg]:size-5 pr-2 pl-3 py-3">
					{ __( 'General', 'surerank' ) }
				</Accordion.Trigger>
				<Accordion.Content>
					<GeneralTab
						postMetaData={ postMetaData }
						updatePostMetaData={ updatePostMetaData }
						globalDefaults={ globalDefaults }
					/>
				</Accordion.Content>
			</Accordion.Item>
			<Accordion.Item
				value="advanced"
				className="bg-background-primary border-none"
			>
				<Accordion.Trigger className="text-base [&>svg]:size-5 pr-2 pl-3 py-3">
					{ __( 'Advanced', 'surerank' ) }
				</Accordion.Trigger>
				<Accordion.Content>
					<AdvancedTab
						postMetaData={ postMetaData }
						updatePostMetaData={ updatePostMetaData }
						globalDefaults={ globalDefaults }
					/>
				</Accordion.Content>
			</Accordion.Item>
			{ ENABLE_SCHEMAS && (
				<Accordion.Item
					value="schema"
					className="bg-background-primary border-none"
				>
					<Accordion.Trigger className="text-base [&>svg]:size-5 pr-2 pl-3 py-3">
						{ __( 'Schema', 'surerank' ) }
					</Accordion.Trigger>
					<Accordion.Content>
						<SchemaTab
							postMetaData={ postMetaData }
							updatePostMetaData={ updatePostMetaData }
							globalDefaults={ globalDefaults }
						/>
					</Accordion.Content>
				</Accordion.Item>
			) }
		</Accordion>
	);
};

export default MetaTab;
