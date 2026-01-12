import styled from '@emotion/styled';
import { Button, Flex } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useStorage from '../hooks/use-storage';
import Typography from '../../../../../assets/dev/js/components/typography';
import { WORDPRESS_REVIEW_LINK } from '../constants';

const ReviewForm = ( { close } ) => {
	const { save, get } = useStorage();

	const handleSubmit = async () => {
		await save( {
			image_optimizer_review_data: {
				...get.data.image_optimizer_review_data,
				repo_review_clicked: true,
			},
		} );

		close();
		window.open( WORDPRESS_REVIEW_LINK, '_blank' );
	};

	return (
		<Flex direction="column">
			<Typography variant="body1" marginBottom={ 1 }>
				{ __( 'It would mean a lot if you left us a quick review, so others can discover it too.', 'image-optimization' ) }
			</Typography>
			<StyledButton
				variant="primary"
				onClick={ handleSubmit }
			>
				{ __( 'Leave a review', 'image-optimization' ) }
			</StyledButton>
		</Flex>
	);
};

export default ReviewForm;

const StyledButton = styled( Button )`
	min-width: 80px;
	align-self: flex-end;
	margin-top: 16px;
	background-color: #515962 !important;
	align-items: center;
	justify-content: center;
	color: #FFF;
`;
