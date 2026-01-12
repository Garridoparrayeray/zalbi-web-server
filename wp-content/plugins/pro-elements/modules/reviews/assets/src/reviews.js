import { ThemeProvider } from '@emotion/react';
import domReady from '@wordpress/dom-ready';
import { StrictMode, Fragment, createRoot } from '@wordpress/element';
import { theme } from './theme';
import ReviewsApp from './app';
import SettingsProvider from './hooks/use-settings';

domReady( () => {
	const rootNode = document.getElementById( 'reviews-app' );

	// Can't use the settings hook in the global scope so accessing directly
	const isDevelopment = window?.imageOptimizerAppSettings?.isDevelopment;
	const AppWrapper = Boolean( isDevelopment ) ? StrictMode : Fragment;

	const root = createRoot( rootNode );

	root.render(
		<AppWrapper>
			<ThemeProvider theme={ theme }>
				<SettingsProvider>
					<ReviewsApp />
				</SettingsProvider>
			</ThemeProvider>
		</AppWrapper>,
	);
} );
