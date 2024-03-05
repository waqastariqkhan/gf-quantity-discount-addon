<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}


class GF_Field_Coupon extends GF_Field {

	public $type = 'coupon';

	/**
	 * Return the field title, for use in the form editor.
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return __( 'Coupon', 'gf-quantity-discount' );
	}

	/**
	 * Assign the Coupon button to the Pricing Fields group.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group' => 'pricing_fields',
			'text'  => $this->get_form_editor_field_title()
		);
	}

	/**
	 * Return the settings which should be available on the field in the form editor.
	 *
	 * @return array
	 */
	function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'label_setting',
			'admin_label_setting',
			'css_class_setting',
			'description_setting',
			'placeholder_setting',
			'visibility_setting',
			'rules_setting',
			'error_message_setting',
		);
	}

	/**
	 * Enable support for using the field with conditional logic.
	 *
	 * @return bool
	 */
	public function is_conditional_logic_supported() {
		return true;
	}

	/**
	 * Returns the fields inner markup.
	 *
	 * @param array $form The form object currently being processed.
	 * @param string $value The field value from the $_POST or the resumed incomplete submission. Not currently used.
	 * @param null $entry
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = '', $entry = null ) {
		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$id              = (int) $this->id;

		if ( $is_entry_detail ) {
			$input = "<input type='hidden' id='input_{$id}' name='input_{$id}' value='{$value}' />";

			return $input . '<br/>' . esc_html__( 'Coupon fields are not editable', 'gf-quantity-discount' );
		}

		$input = "<div class='ginput_container' id='gf_coupons_container_{$form_id}'>" .
				 "<input name='input_{$id}' class='gf_coupon_code_entry'   type='text' "  . '/>' .
		         "<input name='input_{$id}' id='input_{$form_id}_{$id}' class='gf_coupon_code'   type='hidden' "  . '/>' .
		         "<input type='button' value='" . esc_attr__( 'Apply', 'gf-quantity-discount' ) . "' id='gf_coupon_button' class='button'" . $this->get_tabindex() . '/> ' .
		         "<div id='gf_coupon_info'></div>" .
		         "</div>";

		return $input;
	}

	
}

GF_Fields::register( new GF_Field_Coupon() );
