<?php

namespace Simple_History;

// Format a group of items.
abstract class Event_Details_Group_Formatter {
	/**
	 * @param Event_Details_Group $group
	 * @param array<string,mixed> $context
	 * @return string|array<mixed>
	 */
	abstract public function get_output( $group, $context );
}

/**
 * Format a group of items as an inline list.
 */
class Event_Details_Group_Inline_Formatter extends Event_Details_Group_Formatter {
	public function get_output( $group, $context ) {
		$output = '<p>';

		foreach ( $group->items as $item ) {
			$item_formatter = new Event_Details_Item_Default_Formatter( $item );
			$output .= $item_formatter->get_output();
		}

		$output .= '</p>';

		return $output;
	}
}

class Event_Details_Group_Table_Formatter extends Event_Details_Group_Formatter {
	public function get_output( $group, $context ) {
		$output = '<table class="SimpleHistoryLogitem__keyValueTable">';
		$output .= '<tbody>';

		foreach ( $group->items as $item ) {
			$item_formatter = new Event_Details_Item_Table_Row_Formatter( $item );
			$output .= $item_formatter->get_output();
		}

		$output .= '</tbody>';
		$output .= '</table>';

		return $output;
	}
}

/**
 * A group with a single item, just plain output, no table or inline or similar.
 * They are added to the details group without a group first (group is generated in add function).
 */
class Event_Details_Group_Single_Item_Formatter extends Event_Details_Group_Formatter {
	public function get_output( $group, $context ) {
		$output = '';

		foreach ( $group->items as $item ) {
			$name = '';
			if ( ! empty( $item->name ) ) {
				if ( isset( $item->new_value ) ) {
					$name = sprintf( '%1$s: ', esc_html( $item->name ) );
				} else {
					$name = esc_html( $item->name );
				}
			}

			$value = '';
			if ( isset( $item->new_value ) ) {
				$value = esc_html( $item->new_value );
			}

			$output .= sprintf(
				'<p>%1$s%2$s</p>',
				$name,
				$value
			);
		}

		return $output;
	}
}

// Format a single item in a group,
// i.e. output current value and previous value, if any.
abstract class Event_Details_Item_Formatter {
	/**
	 * @var Event_Details_Item $item
	 */
	protected $item;

	/**
	 * @param Event_Details_Item $item
	 */
	public function __construct( $item ) {
		$this->item = $item;
	}

	/**
	 * @return string
	 */
	abstract public function get_output();

	/**
	 * @return string
	 */
	protected function get_value_diff_output() {
		$value_output = '';

		if ( $this->item->is_changed ) {
			$value_output = sprintf(
				'
				<ins class="SimpleHistoryLogitem__keyValueTable__addedThing">%1$s</ins>
				<del class="SimpleHistoryLogitem__keyValueTable__removedThing">%2$s</del>	
				',
				esc_html( $this->item->new_value ),
				esc_html( $this->item->prev_value )
			);
		} else {
			$value_output = sprintf(
				'<span class="SimpleHistoryLogitem__keyValueTable__addedThing">%1$s</span>',
				esc_html( $this->item->new_value )
			);
		}

		return $value_output;
	}
}

class Event_Details_Item_Default_Formatter extends Event_Details_Item_Formatter {
	public function get_output() {

		$name = '';
		if ( ! empty( $this->item->name ) ) {
			$name = sprintf( '<em>%1$s:</em> ', esc_html( $this->item->name ) );
		}

		return sprintf(
			'<span class="SimpleHistoryLogitem__inlineDivided">%1$s%2$s</span> ',
			$name,
			$this->get_value_diff_output(),
		);
	}
}

class Event_Details_Item_Table_Row_Formatter extends Event_Details_Item_Formatter {
	public function get_output() {
		// Skip output of items with empty values.
		if ( is_null( $this->item->new_value ) ) {
			return '';
		}

		return sprintf(
			'
                <tr>
                    <td>%1$s</td>
                    <td>%2$s</td>
                </tr>
            ',
			esc_html( $this->item->name ),
			$this->get_value_diff_output()
		);
	}
}

class Event_Details_Item_HTML_Formatter extends Event_Details_Item_Formatter {
	public function get_output() {
		return 'TODO: HTML output';
	}
}
