import { Snackbar } from '@wordpress/components';
import { useSettings } from '../hooks/use-settings';

const ReviewNotifications = ( { message } ) => {
	const {
		showNotification,
		setShowNotification,
		setNotificationMessage,
		setNotificationType,
	} = useSettings();

	const closeNotification = () => {
		setShowNotification( ! showNotification );
		setNotificationMessage( '' );
		setNotificationType( '' );
	};

	if ( ! showNotification ) {
		return null;
	}

	return (
		<Snackbar
			onRemove={ closeNotification }
		>
			{ message }
		</Snackbar>
	);
};

export default ReviewNotifications;
