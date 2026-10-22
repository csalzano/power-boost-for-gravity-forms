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
	 * Keeps track of imported form IDs that have had feeds deleted during this request.
	 *
	 * @var array
	 */
	protected $form_ids_with_deleted_feeds = array();

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

		// Write .json files when add-on feeds are saved.
		add_action( 'gform_post_save_feed_settings', array( $this, 'save_form_export_meta_saved' ), 10, 2 );

		// Write .json files when add-on feeds are deleted.
		add_action( 'gform_pre_delete_feed', array( $this, 'track_deleted_feed' ), 10, 1 );
		add_action( 'shutdown', array( $this, 'save_form_export_feeds_deleted' ) );

		// Add a toolbar button to load the .json file.
		add_filter( 'gform_toolbar_menu', array( $this, 'add_toolbar_button' ), 10, 2 );

		// Register a Gravity Forms add-on.
		add_action( 'gform_loaded', array( $this, 'register_addon' ) );
	}

	/**
	 * Saves the IDs of forms that have had feeds deleted during this request.
	 *
	 * @param  int $feed_id The ID of the feed being deleted.
	 * @return void
	 */
	public function track_deleted_feed( $feed_id ) {
		global $wpdb;
		$cache_key = 'gfpb_feed_form_id_' . $feed_id;
		$form_id   = wp_cache_get( $cache_key );
		if ( false === $form_id ) {
			$form_id = $wpdb->get_var( $wpdb->prepare( "SELECT form_id FROM {$wpdb->prefix}gf_addon_feed WHERE id = %d", $feed_id ) );
			wp_cache_set( $cache_key, $form_id, '', 24 * HOUR_IN_SECONDS );
		}
		if ( ! empty( $form_id ) ) {
			$this->form_ids_with_deleted_feeds[] = $form_id;
		}
	}

	/**
	 * Runs on shutdown hook to create backups of forms that had feeds deleted.
	 *
	 * @return void
	 */
	public function save_form_export_feeds_deleted() {
		if ( empty( $this->form_ids_with_deleted_feeds ) ) {
			return;
		}
		foreach ( $this->form_ids_with_deleted_feeds as $form_id ) {
			$form = GFAPI::get_form( $form_id );
			if ( false === $form ) {
				continue;
			}
			self::save_form_export( $form );
		}
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
			wp_mkdir_p( $dir );
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

		/*
		 * A missing inputType and a null inputType are the same value.
		 * GF_Field::__get() and offsetGet() store "" the first time something
		 * reads the property. Form saves run this export twice, so a read
		 * between the two hooks used to rewrite the file.
		 */
		$forms = self::strip_empty_input_types( $forms );

		// get path where we are saving .json files, {form_id}.json.
		$save_path = self::json_file_path( $form['id'] );

		$json = apply_filters( 'gravityforms_local_json_minimize', false ) ? wp_json_encode( $forms ) : wp_json_encode( $forms, JSON_PRETTY_PRINT );

		// Would the file be different?
		if ( file_exists( $save_path ) && self::export_json_is_unchanged( file_get_contents( $save_path ), $json ) ) {
			// No. Do not update it, preserve the file date.
			return;
		}

		// write the file.
		file_put_contents( $save_path, $json );
	}

	/**
	 * A wrapper method for save_form_export() that is called when a form's meta
	 * is updated or a feed is saved. This triggers a .json file write when
	 * confirmations or notifications are updated. It also handles add-ons that
	 * add their feed data to form exports, like GravityFlow.
	 *
	 * @param  mixed $ignored The name of the form meta updated, or a feed ID.
	 * @param  int   $form_id The ID of the form data was updated.
	 * @return void
	 */
	public static function save_form_export_meta_saved( $ignored, $form_id ) {
		if ( ! class_exists( 'GFAPI' ) ) {
			return;
		}
		$form = GFAPI::get_form( $form_id );
		if ( false === $form ) {
			return;
		}
		self::save_form_export( $form );
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
		$form = GFAPI::get_form( $form_id );
		if ( false === $form ) {
			return;
		}
		self::save_form_export( $form );
	}

	/**
	 * Whether two export JSON strings describe the same form.
	 *
	 * Empty inputType values are ignored. null, "", and an omitted property
	 * all mean "use the field type", and Gravity Forms rewrites between those
	 * spellings in a single request.
	 *
	 * @param string $existing JSON already stored in the export file.
	 * @param string $json     JSON this request would write.
	 * @return bool
	 */
	protected static function export_json_is_unchanged( $existing, $json ) {
		if ( $existing === $json ) {
			return true;
		}

		$existing_data = json_decode( $existing, true );
		$new_data      = json_decode( $json, true );
		if ( ! is_array( $existing_data ) || ! is_array( $new_data ) ) {
			return false;
		}

		$existing_data = self::strip_empty_input_types( $existing_data );
		$new_data      = self::strip_empty_input_types( $new_data );

		return wp_json_encode( $existing_data ) === wp_json_encode( $new_data );
	}

	/**
	 * Removes inputType values that do not change the field.
	 *
	 * Walks each form in the export payload from GFExport::prepare_forms_for_export(),
	 * including nested repeater fields. Field objects
	 * are the same instances Gravity Forms keeps in its form cache, so this
	 * also clears an empty inputType that a property read just materialized.
	 *
	 * @param array $forms Export payload, or a list of fields.
	 * @return array
	 */
	protected static function strip_empty_input_types( $forms ) {
		if ( ! is_array( $forms ) ) {
			return $forms;
		}

		foreach ( $forms as $key => $export_form ) {
			if ( ! is_array( $export_form ) ) {
				continue;
			}

			if ( isset( $export_form['fields'] ) && is_array( $export_form['fields'] ) ) {
				$forms[ $key ]['fields'] = self::strip_empty_input_types_from_fields( $export_form['fields'] );
				continue;
			}

			$forms[ $key ] = self::strip_empty_input_type_from_field( $export_form );
		}

		return $forms;
	}

	/**
	 * Removes empty inputType values from each field in a list.
	 *
	 * @param array $fields Field arrays or GF_Field objects.
	 * @return array
	 */
	protected static function strip_empty_input_types_from_fields( $fields ) {
		foreach ( $fields as $index => $field ) {
			$fields[ $index ] = self::strip_empty_input_type_from_field( $field );
		}

		return $fields;
	}

	/**
	 * Removes an empty inputType from one field and from fields nested in it.
	 *
	 * Reading a missing inputType on a GF_Field stores "". property_exists()
	 * is used so a field that never had the property is left untouched.
	 *
	 * @param mixed $field A field array or object.
	 * @return mixed
	 */
	protected static function strip_empty_input_type_from_field( $field ) {
		if ( is_array( $field ) ) {
			$type = isset( $field['type'] ) ? $field['type'] : null;
			if ( array_key_exists( 'inputType', $field ) && self::input_type_is_empty( $field['inputType'], $type ) ) {
				unset( $field['inputType'] );
			}
			if ( isset( $field['fields'] ) && is_array( $field['fields'] ) ) {
				$field['fields'] = self::strip_empty_input_types_from_fields( $field['fields'] );
			}
			return $field;
		}

		if ( ! is_object( $field ) ) {
			return $field;
		}

		if ( property_exists( $field, 'inputType' ) ) {
			$type = property_exists( $field, 'type' ) ? $field->type : null;
			if ( self::input_type_is_empty( $field->inputType, $type ) ) {
				unset( $field->inputType );
			}
		}

		if ( isset( $field->fields ) && is_array( $field->fields ) ) {
			$field->fields = self::strip_empty_input_types_from_fields( $field->fields );
		}

		return $field;
	}

	/**
	 * Whether an inputType value adds nothing beyond the field type.
	 *
	 * @param mixed $input_type Stored input type.
	 * @param mixed $type       Field type.
	 * @return bool
	 */
	protected static function input_type_is_empty( $input_type, $type ) {
		return null === $input_type || '' === $input_type || ( null !== $type && '' !== $type && $input_type === $type );
	}
}
