<?php
/**
 * Maintains .json file form backups when forms are created or updated.
 *
 * @package Gravity_Forms_Power_Boost
 */

defined( 'ABSPATH' ) || exit;

/**
 * GFPB_Local_JSON
 */
class GFPB_Local_JSON {

	const ACTION = 'local_json_load';
	const NONCE  = 'local_json_nonce';

	/**
	 * Adds hooks that power the local JSON feature.
	 *
	 * @return void
	 */
	public function add_hooks() {
		// Write a .json file when a form is created or updated.
		add_action( 'gform_after_save_form', array( __CLASS__, 'save_form_export' ), 10, 1 );
		// Write the same .json file when confirmations or notifications are created or updated.
		add_action( 'gform_post_update_form_meta', array( __CLASS__, 'save_form_export_meta_saved' ), 10, 2 );

		// Write a .json file when a form is activated or deactivated.
		add_action( 'gform_post_form_activated', array( $this, 'save_form_export_after_status_change' ), 10, 1 );
		add_action( 'gform_post_form_deactivated', array( $this, 'save_form_export_after_status_change' ), 10, 1 );

		// Write .json files for all forms when this plugin is activated.
		register_activation_hook( GF_POWER_BOOST_PLUGIN_ROOT, array( __CLASS__, 'save_form_export_activate' ) );

		// Add a toolbar button to load the .json file.
		add_filter( 'gform_toolbar_menu', array( $this, 'add_toolbar_button' ), 10, 2 );

		// Register a Gravity Forms add-on.
		add_action( 'gform_loaded', array( $this, 'register_addon' ) );
	}

	/**
	 * Adds a button to the Gravity Forms toolbar named "Local JSON" if the
	 * form the user is looking at has a .json file that could be loaded.
	 *
	 * @param  array $menu_items An array of menu items.
	 * @param  int   $form_id A form ID.
	 * @return array The changed or unchanged $menu_items array
	 */
	public function add_toolbar_button( $menu_items, $form_id ) {
		// does this form even have a .json file?
		if ( ! self::have_json( $form_id ) ) {
			return $menu_items;
		}

		$menu_items['localjson'] = array(
			'label'        => esc_html__( 'Local JSON', 'power-boost-for-gravity-forms' ),
			'short_label'  => esc_html__( 'Local JSON', 'power-boost-for-gravity-forms' ),
			'aria-label'   => esc_html__( 'Update this form using the local JSON file.', 'power-boost-for-gravity-forms' ),
			'icon'         => '<i class="fa fa-pencil-square-o fa-lg"></i>',
			'url'          => admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=localjson&id=' . $form_id ),
			'menu_class'   => 'gf_form_toolbar_localjson',
			'link_class'   => rgget( 'view' ) === 'localjson' ? 'gf_toolbar_active' : '',
			'capabilities' => array( 'gravityforms_edit_forms' ),
			'priority'     => 900,
		);

		return $menu_items;
	}

	/**
	 * Does a .json file backup for this form exist?
	 *
	 * @param  int $form_id A form ID.
	 * @return bool
	 */
	public static function have_json( $form_id ) {
		if ( empty( $form_id ) || ! is_numeric( $form_id ) ) {
			return false;
		}
		return file_exists( self::json_file_path( $form_id ) );
	}

	/**
	 * Returns a full file path to a form's backup .json file.
	 *
	 * @param  int $form_id A form ID.
	 * @return string
	 */
	public static function json_file_path( $form_id ) {
		return self::json_save_path() . DIRECTORY_SEPARATOR . $form_id . '.json';
	}

	/**
	 * Returns a directory path where the .json files are stored.
	 *
	 * @return string
	 */
	public static function json_save_path() {
		$upload_dir = wp_get_upload_dir();
		$dir        = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'gf-json';
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir );
		}
		return apply_filters( 'gravityforms_local_json_save_path', $dir );
	}

	/**
	 * Adds an add-on so the Local JSON feature shows up in each form's
	 * settings.
	 *
	 * @return void
	 */
	public function register_addon() {
		include_once __DIR__ . DIRECTORY_SEPARATOR . 'class-gfpb-local-json-addon.php';
		GFAddOn::register( 'GFPB_Local_JSON_Addon' );
	}

	/**
	 * Saves a .json file that contains a text backup of a form.
	 *
	 * @param  array $form Gravity Forms form array.
	 * @return void
	 */
	public static function save_form_export( $form ) {
		if ( ! class_exists( 'GFExport' ) ) {
			return;
		}

		// create an export of this form.
		$forms            = GFExport::prepare_forms_for_export( array( $form ) );
		$forms['version'] = GFForms::$version;

		// touch the forms before they're saved with this filter.
		$forms = apply_filters( 'gravityforms_local_json_save_form', $forms );

		// get path where we are saving .json files, {form_id}.json.
		$save_path = self::json_file_path( $form['id'] );

		$json = apply_filters( 'gravityforms_local_json_minimize', false ) ? wp_json_encode( $forms ) : wp_json_encode( $forms, JSON_PRETTY_PRINT );

		// Would the file be different?
		if ( file_exists( $save_path ) && file_get_contents( $save_path ) === $json ) {
			// No. Do not update it, preserve the file date.
			return;
		}

		// write the file.
		file_put_contents( $save_path, $json );
	}

	/**
	 * A wrapper method for save_form_export() that is called when a form's meta
	 * is updated. This triggers a .json file write when confirmations or
	 * notifications are updated.
	 *
	 * @param  string $form_meta The name of the meta updated.
	 * @param  int    $form_id The ID of the form data was updated.
	 * @return void
	 */
	public static function save_form_export_meta_saved( $form_meta, $form_id ) {
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}
		self::save_form_export( GFAPI::get_form( $form_id ) );
	}

	/**
	 * Save form export .json files for all forms. Callback method the a plugin
	 * activation hook.
	 *
	 * @return void
	 */
	public static function save_form_export_activate() {
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}

		// Get all forms.
		$forms = GFAPI::get_forms( null );
		if ( empty( $forms ) ) {
			return;
		}

		foreach ( $forms as $form ) {
			// Is this form active?
			if ( '0' === $form['is_active'] ) {
				// No. Skip it.
				continue;
			}
			self::save_form_export( $form );
		}
	}

	/**
	 * Updates the .json file after a form status change between active and
	 * inactive.
	 *
	 * @param  mixed $form_id A form ID.
	 * @return void
	 */
	public function save_form_export_after_status_change( $form_id ) {
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}
		self::save_form_export( GFAPI::get_form( $form_id ) );
	}
}
