import { Tooltip } from '@wordpress/components';
import { dateI18n, getSettings as getDateSettings } from '@wordpress/date';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { intlFormatDistance } from 'date-fns';

export function EventDate( props ) {
	const { event } = props;

	const dateSettings = getDateSettings();
	const dateFormat = dateSettings.formats.datetime;
	const dateFormatAbbreviated = dateSettings.formats.datetimeAbbreviated;

	const formattedDateFormatAbbreviated = dateI18n(
		dateFormatAbbreviated,
		event.date_gmt
	);

	const [ formattedDateLiveUpdated, setFormattedDateLiveUpdated ] = useState(
		() => {
			return intlFormatDistance( event.date_gmt, new Date() );
		}
	);

	useEffect( () => {
		const intervalId = setInterval( () => {
			setFormattedDateLiveUpdated(
				intlFormatDistance( event.date_gmt, new Date() )
			);
		}, 1000 );

		return () => {
			clearInterval( intervalId );
		};
	}, [ event.date_gmt ] );

	const tooltipText = sprintf(
		__( '%1$s local time %3$s (%2$s GMT time)', 'simple-history' ),
		event.date,
		event.date_gmt,
		'\n'
	);

	return (
		<span className="SimpleHistoryLogitem__permalink SimpleHistoryLogitem__when SimpleHistoryLogitem__inlineDivided">
			<Tooltip text={ tooltipText } delay={ 500 }>
				<a href="#">
					<time
						datetime={ event.date_gmt }
						className="SimpleHistoryLogitem__when__liveRelative"
					>
						{ formattedDateFormatAbbreviated } (
						{ formattedDateLiveUpdated })
					</time>
				</a>
			</Tooltip>
		</span>
	);
}