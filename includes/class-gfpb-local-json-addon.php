<?php
/**
 * Registers an add-on so a Local JSON tab appears in each form's settings.
 *
 * @package Gravity_Forms_Power_Boost
 */

defined( 'ABSPATH' ) || exit;

if ( method_exists( 'GFForms', 'include_addon_framework' ) ) {
	GFForms::include_addon_framework();
}

/**
 * GFPB_Local_JSON_Addon
 *
 * This is an implementation of the Gravity Forms add-on class to achieve one
 * goal: we want a form settings tab on each form.
 */
class GFPB_Local_JSON_Addon extends GFAddOn {

	/**
	 * Add-on version number.
	 *
	 * @var string
	 */
	protected $_version = '1.0.0';

	/**
	 * Minimum Gravity Forms version number required to run this add-on.
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '1.9';

	/**
	 * The add-on slug doubles as the key in which all the settings are stored. If this changes, also change uninstall.php where the string is hard-coded.
	 *
	 * @var $_slug  string The add-on slug doubles as the key in which all the settings are stored. If this changes, also change uninstall.php where the string is hard-coded.
	 * @see get_slug()
	 */
	protected $_slug = 'localjson';

	/**
	 * The relative path to this file.
	 *
	 * @var string
	 */
	protected $_path = 'power-boost-for-gravity-forms/includes/class-gfpb-local-json-addon.php';

	/**
	 * An alias for __FILE__
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * The title of this add-on.
	 *
	 * @var string
	 */
	protected $_title = 'Local JSON';

	/**
	 * A shorter title for this add-on so it fits in the tab UI nicely.
	 *
	 * @var string
	 */
	protected $_short_title = 'Local JSON';

	/**
	 * Where an instance of this class is stored to achieve a singleton pattern.
	 *
	 * @var object
	 */
	private static $_instance = null;

	/**
	 * Members plugin integration
	 *
	 * @var $_capabilities array
	 */
	protected $_capabilities = array(
		'gravityforms_local_json',
		'gravityforms_local_json_uninstall',
		'gravityforms_local_json_results',
		'gravityforms_local_json_settings',
		'gravityforms_local_json_form_settings',
	);

	/**
	 * Capabilities slugs used to enforce permissions.
	 *
	 * @var string
	 */
	protected $_capabilities_settings_page = 'gravityforms_local_json_settings';

	/**
	 * Capabilities slugs used to enforce permissions.
	 *
	 * @var string
	 */
	protected $_capabilities_form_settings = 'gravityforms_local_json_form_settings';

	/**
	 * Capabilities slugs used to enforce permissions.
	 *
	 * @var string
	 */
	protected $_capabilities_uninstall = 'gravityforms_local_json_uninstall';

	/**
	 * Get an instance of this class.
	 *
	 * @return GFPB_Local_JSON_Addon
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new GFPB_Local_JSON_Addon();
		}

		return self::$_instance;
	}

	/**
	 * Return the plugin's icon for the plugin/form settings menu.
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return 'gform-icon--circle-arrow-down';
	}

	/**
	 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
	 *
	 * @param  array $form A form array.
	 * @return array
	 */
	public function form_settings_fields( $form ) {
		$field_title = esc_html__( 'Available', 'gravityforms-local-json' );

		// Do we have a .json file for this form?
		$form_id = rgget( 'id' );
		if ( ! GFPB_Local_JSON::have_json( $form_id ) ) {
			$field_title = esc_html__( 'Unavailable', 'gravityforms-local-json' );
		}

		return array(
			array(
				'title'  => esc_html__( 'Load From File', 'gravityforms-local-json' ),
				'fields' => array(
					array(
						'label'   => $field_title,
						'type'    => 'availability',
						'name'    => 'enabled',
						'tooltip' => esc_html__( 'JSON form export files can be used to update forms during deployments.', 'gravityforms-local-json' ),
					),
				),
			),
			array(
				'title'  => esc_html__( 'File Location', 'gravityforms-local-json' ),
				'fields' => array(
					array(
						'label'   => 'JSON Form Exports Path',
						'type'    => 'files_location',
						'name'    => 'enabled',
						'tooltip' => esc_html__( 'Where does this plugin store the form export .json files?', 'gravityforms-local-json' ),
					),
				),
			),
		);
	}

	/**
	 * Add-on initialization.
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		// Check if the form that loads a .json file was just submitted.
		$this->maybe_load_json();

		// Don't show the Save Settings button on this add-on's page, there are no settings.
		add_filter( 'gform_settings_save_button', array( $this, 'remove_addon_save_button' ), 10, 1 );
	}

	/**
	 * Checks to see if the user is loading a .json file and does exactly that.
	 *
	 * @return void
	 */
	public function maybe_load_json() {
		// is this our form POST?
		if ( ! is_admin()
			|| 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) )
			|| GFPB_Local_JSON::ACTION !== rgpost( 'action' ) ) {
			return;
		}

		// check the nonce.
		if ( ! check_admin_referer( GFPB_Local_JSON::ACTION, GFPB_Local_JSON::NONCE ) ) {
			return;
		}

		$form_id = (int) rgpost( 'form_id' );

		// does the form exist?
		if ( false === GFAPI::get_form( $form_id ) ) {
			// no.
			return;
		}

		// do we even have a .json file?
		$json = file_get_contents( GFPB_Local_JSON::json_file_path( $form_id ) );
		if ( false === $json ) {
			// no.
			return;
		}
		$forms_array = json_decode( $json, true );
		foreach ( $forms_array as $form ) {
			if ( empty( $form['id'] ) || $form['id'] !== $form_id ) {
				continue;
			}
			GFAPI::update_form( $form, $form_id );
		}
	}

	/**
	 * Removes the Save button at the bottom of our add-ons page in each form's
	 * settings.
	 *
	 * @param  string $html HTML that renders an add-on's settings page save button.
	 * @return string
	 */
	public function remove_addon_save_button( $html ) {
		// is this our addon's page?
		if ( rgget( 'subview' ) === self::get_instance()->get_slug() ) {
			// yes, return empty string instead of HTML that creates a Save Settings button.
			return '';
		}
		return $html;
	}

	/**
	 * Handler method for a settings field of type files_location.
	 *
	 * @param  GF_Field $field A field object.
	 * @param  bool     $echo Whether to output or return the field HTML.
	 * @return string
	 */
	public function settings_files_location( $field, $echo = true ) {
		$html = sprintf(
			'<code>%s</code><p>%s</p>',
			GFPB_Local_JSON::json_save_path(),
			__( 'Power Boost for Gravity Forms generates a .json export of each form that is created or modified at the location above. Use the filter hook <i>gravityforms_local_json_save_path</i> to change this folder path.', 'power-boost-for-gravity-forms' )
		);

		if ( $echo ) {
			echo esc_html( $html );
			return;
		}
		return $html;
	}

	/**
	 * Handler method for a settings field of type availability.
	 *
	 * @param  array $field The add-on settings field for which this method outputs HTML controls.
	 * @param  bool  $echo Whether to output or return the field HTML.
	 * @return string|void Returns a string if $echo is false. Returns void if $echo is true.
	 */
	public function settings_availability( $field, $echo = true ) {
		$html = '';

		// Do we have a .json file for this form?
		$form_id = rgget( 'id' );
		if ( ! GFPB_Local_JSON::have_json( $form_id ) ) {
			// No, instruct the user to save this form to generate the file.
			$html .= 'No .json file exists for this form. Re-save the form to create one.';
		} else {
			// What is the date on the file?
			$html .= '<p>File last modified on ' . gmdate( 'F d, Y @ H:i:s', filemtime( GFPB_Local_JSON::json_file_path( $form_id ) ) ) . '</p>'
				. '<p>Click the <b>Load</b> button below to update this form and match the .json file.</p>'
				. '<form method="POST" id="local_json_form">'
				. '<input type="hidden" name="action" value="' . GFPB_Local_JSON::ACTION . '" />'
				. '<input type="hidden" name="form_id" value="' . $form_id . '" />'
				. wp_nonce_field( GFPB_Local_JSON::ACTION, GFPB_Local_JSON::NONCE, true, false )
				. '<button class="button" onclick="document.getElementById(\'local_json_form\').submit(); return false;">Load</button>'
				. '</form>';
		}

		if ( $echo ) {
			echo esc_html( $html );
			return;
		}
		return $html;
	}
}
