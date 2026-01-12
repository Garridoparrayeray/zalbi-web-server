import styled from '@emotion/styled';
import { Button, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSettings } from '../hooks/use-settings';

const FeedbackForm = ( { close, handleSubmitForm } ) => {
	const { feedback, setFeedback } = useSettings();

	return (
		<StyledContainer>
			<StyledTextareaControl
				value={ feedback }
				onChange={ setFeedback }
				placeholder={ __( 'Share your thoughts on how we can improve Image Optimizer…', 'image-optimization' ) }
				rows={ 5 }
			/>
			<StyledButton
				variant="primary"
				onClick={ () => handleSubmitForm( close ) }
			>
				{ __( 'Submit', 'image-optimization' ) }
			</StyledButton>
		</StyledContainer>
	);
};

export default FeedbackForm;

const StyledContainer = styled.div`
	display: flex;
	flex-direction: column;
	width: 100%;
`;

const StyledButton = styled( Button )`
	min-width: 80px;
	align-self: flex-end;
	margin-top: 16px;
	background-color: #515962 !important;
	align-items: center;
	justify-content: center;
`;

const StyledTextareaControl = styled( TextareaControl )`
	margin-bottom: 16px;
	
	textarea {
		min-height: 120px;
	}
	
	textarea:focus, 
	textarea:active {
		outline: none;
		box-shadow: 0 0 0 1px #007cba;
	}
`;
