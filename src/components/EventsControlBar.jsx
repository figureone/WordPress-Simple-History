import {
	DropdownMenu,
	Flex,
	FlexItem,
	__experimentalHStack as HStack,
	Icon,
	MenuGroup,
	MenuItem,
	Spinner,
	__experimentalText as Text,
} from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import { lockSmall, moreVertical } from '@wordpress/icons';
import { NewEventsNotifier } from './NewEventsNotifier';

const PremiumFeatureSuffix = function () {
	return (
		<span
			style={ {
				color: 'darkgreen',
				fontSize: '0.8em',
				border: '1px solid darkgreen',
				borderRadius: '5px',
				padding: '0.2em 0.5em',
				opacity: '0.8',
				lineHeight: 1,
			} }
		>
			{ __( 'Premium', 'simple-history' ) }
		</span>
	);
};

const PremiumFeatureSuffixSmaller = function () {
	return (
		<span
			style={ {
				color: 'darkgreen',
				fontSize: '0.8em',
				border: '1px solid darkgreen',
				borderRadius: '5px',
				xpadding: '0.1em 0.3em',
				opacity: '0.75',
				lineHeight: 1,
			} }
		>
			<Icon icon={ lockSmall } />
		</span>
	);
};

const MyDropdownMenu = () => (
	<DropdownMenu
		label={ __( 'Actions…', 'simple-history' ) }
		icon={ moreVertical }
	>
		{ ( { onClose } ) => (
			<>
				<MenuGroup>
					<MenuItem
						onClick={ onClose }
						info="Re-use this search in the future"
					>
						Save search
					</MenuItem>
					<MenuItem onClick={ onClose }>Copy link to search</MenuItem>
				</MenuGroup>
				<MenuGroup label="Export">
					<MenuItem
						onClick={ onClose }
						suffix={ <PremiumFeatureSuffix /> }
					>
						Export as CSV...
					</MenuItem>
					<MenuItem
						onClick={ onClose }
						suffix={ <PremiumFeatureSuffix /> }
					>
						Export as JSON
					</MenuItem>
				</MenuGroup>
				<MenuGroup>
					<MenuItem
						onClick={ onClose }
						suffix={ <PremiumFeatureSuffixSmaller /> }
					>
						Add event manually
					</MenuItem>
					<MenuItem
						onClick={ onClose }
						suffix={ <PremiumFeatureSuffixSmaller /> }
					>
						Send log via email
					</MenuItem>
				</MenuGroup>
			</>
		) }
	</DropdownMenu>
);

/**
 * Control bar at the top with number of events, reload button, more actions like export and so on.
 *
 * @param {Object} props
 */
export function EventsControlBar( props ) {
	const {
		eventsIsLoading,
		eventsTotal,
		eventsQueryParams,
		eventsMaxId,
		onReload,
	} = props;

	const loadingIndicator = eventsIsLoading ? (
		<Text>
			<Spinner />

			{ _x(
				'Loading…',
				'Message visible while waiting for log to load from server the first time',
				'simple-history'
			) }
		</Text>
	) : null;

	const eventsCount = eventsTotal ? (
		<Text>{ eventsTotal } events</Text>
	) : null;

	return (
		<div style={ { background: 'white', padding: '6px 12px' } }>
			<Flex gap={ 2 } style={ {} }>
				<FlexItem>
					<HStack spacing={ 2 }>
						{ eventsCount }

						<NewEventsNotifier
							eventsQueryParams={ eventsQueryParams }
							eventsMaxId={ eventsMaxId }
							onReload={ onReload }
						/>

						{ loadingIndicator }
					</HStack>
				</FlexItem>

				<FlexItem>
					<MyDropdownMenu />
				</FlexItem>
			</Flex>
		</div>
	);
}
