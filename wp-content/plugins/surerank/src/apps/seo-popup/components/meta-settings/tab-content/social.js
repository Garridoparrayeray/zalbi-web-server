import SocialTab from './meta/social-tab';

const Social = ( { postMetaData, updatePostMetaData, globalDefaults } ) => {
	return (
		<div className="p-2 gap-2">
			<div className="w-full bg-background-secondary flex flex-col items-center justify-center rounded p-1">
				<SocialTab
					postMetaData={ postMetaData }
					updatePostMetaData={ updatePostMetaData }
					globalDefaults={ globalDefaults }
				/>
			</div>
		</div>
	);
};

export default Social;
