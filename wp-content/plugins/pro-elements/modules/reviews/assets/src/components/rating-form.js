import styled from '@emotion/styled';
import { RadioControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSettings } from '../hooks/use-settings';
import { MoodEmpty, MoodHappy, MoodSad, MoodSadSquint, MoodSmile } from '../icons';

const RatingForm = ( { close, handleSubmitForm } ) => {
	const {
		rating,
		setRating,
		setCurrentPage,
		nextButtonDisabled,
		setNextButtonDisabled,
	} = useSettings();

	const ratingsMap = [
		{ value: '5', label: __( 'Excellent', 'image-optimization' ), icon: <MoodHappy /> },
		{ value: '4', label: __( 'Pretty good', 'image-optimization' ), icon: <MoodSmile /> },
		{ value: '3', label: __( "It's okay", 'image-optimization' ), icon: <MoodEmpty /> },
		{ value: '2', label: __( 'Could be better', 'image-optimization' ), icon: <MoodSadSquint /> },
		{ value: '1', label: __( 'Needs improvement', 'image-optimization' ), icon: <MoodSad /> },
	];

	const handleRatingChange = ( value ) => {
		setRating( value );
		setNextButtonDisabled( false );
	};

	const handleNextButton = async () => {
		if ( parseInt( rating ) < 4 ) {
			setCurrentPage( 'feedback' );
		} else {
			const submitted = await handleSubmitForm( close, true );

			if ( submitted ) {
				setCurrentPage( 'review' );
			}
		}
	};

	// Convert ratingsMap to options format expected by RadioControl
	const options = ratingsMap.map( ( { value, label, icon } ) => ( {
		label: (
			<StyledRadioOption key={ value }>
				<span className="rating-icon">{ icon }</span>
				<span className="rating-label">{ label }</span>
			</StyledRadioOption>
		),
		value,
	} ) );

	return (
		<StyledContainer>
			<StyledRadioControl
				selected={ rating }
				options={ options }
				onChange={ handleRatingChange }
			/>
			<StyledButton
				variant="primary"
				onClick={ handleNextButton }
				disabled={ nextButtonDisabled }
			>
				{ __( 'Next', 'image-optimization' ) }
			</StyledButton>
		</StyledContainer>
	);
};

export default RatingForm;

const StyledContainer = styled.div`
	display: flex;
	flex-direction: column;
	width: 100%;
`;

const StyledRadioOption = styled.div`
	display: flex;
	justify-content: space-between;
	align-items: center;
	width: 100%;
	
	.rating-icon {
		margin-right: 8px;
	}
	
	.rating-label {
		flex: 1;
	}
`;

const StyledButton = styled( Button )`
	min-width: 80px;
	align-self: flex-end;
	margin-top: 16px;
	background-color: #515962 !important;
	align-items: center;
	justify-content: center;
`;

const StyledRadioControl = styled( RadioControl )`
	.components-radio-control__option {
		display: flex;
		align-items: center;
		justify-content: space-between;
		flex-direction: row-reverse;

		.components-radio-control__input[type=radio]:checked {
			background-color: #515962 !important;
			border-color: #515962 !important;
		}
	}
`;
