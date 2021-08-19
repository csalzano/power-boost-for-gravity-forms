<?php
defined( 'ABSPATH' ) or exit;

class Gravity_Forms_Local_JSON
{
	const ACTION = 'local_json_load';
	const NONCE = 'local_json_nonce';

	public function add_hooks()
	{
		//Write a .json file when a form is created or updated
		add_action( 'gform_after_save_form', array( $this, 'save_form_export' ), 10, 2 );

		//Write a .json file when a form is activated or deactivated
		add_action( 'gform_post_form_activated', array( $this, 'save_form_export_after_status_change' ), 10, 1 );
		add_action( 'gform_post_form_deactivated', array( $this, 'save_form_export_after_status_change' ), 10, 1 );

		//Add a toolbar button to load the .json file
		add_filter( 'gform_toolbar_menu', array( $this, 'add_toolbar_button' ), 10, 2 );

		//Register a Gravity Forms add-on
		add_action( 'gform_loaded', array( $this, 'register_addon' ) );
	}
	
	/**
	 * add_toolbar_button
	 * 
	 * Adds a button to the Gravity Forms toolbar named "Local JSON" if the
	 * form the user is looking at has a .json file that could be loaded.
	 *
	 * @param  array $menu_items
	 * @param  int $form_id
	 * @return array The changed or unchanged $menu_items array
	 */
	public function add_toolbar_button( $menu_items, $form_id )
	{
		//does this form even have a .json file?
		if( ! self::have_json( $form_id ) )
		{
			return $menu_items;
		}

		$menu_items['localjson'] = array(
			'label'          => esc_html__( 'Local JSON', 'gravityforms' ),
			'short_label'    => esc_html__( 'Local JSON', 'gravityforms' ),
			'aria-label'     => esc_html__( 'Update this form using the local JSON file.', 'gravityforms' ),
			'icon'           => '<i class="fa fa-pencil-square-o fa-lg"></i>',
			'url'            => admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=localjson&id=' . $form_id ),
			'menu_class'     => 'gf_form_toolbar_localjson',
			'link_class'     => rgget( 'view' ) == 'localjson' ? 'gf_toolbar_active' : '',
			'capabilities'   => array( 'gravityforms_edit_forms' ),
			'priority'       => 900,
		);

		return $menu_items;
	}

	public static function have_json( $form_id )
	{
		if( empty( $form_id ) || ! is_numeric( $form_id ) )
		{
			return false;
		}
		return file_exists( self::json_file_path( $form_id ) );
	}

	public static function json_file_path( $form_id )
	{
		return self::json_save_path() . DIRECTORY_SEPARATOR . $form_id . '.json';
	}

	public static function json_save_path()
	{
		$upload_dir = wp_get_upload_dir();
		$dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'gf-json';
		if( ! is_dir( $dir ) )
		{
		  mkdir( $dir );
		}
		return apply_filters( 'gravityforms_local_json_save_path', $dir );
	}

	public function register_addon()
	{
		include_once __DIR__ . DIRECTORY_SEPARATOR . 'local-json-addon.php';
		GFAddOn::register( 'Gravity_Forms_Local_JSON_Addon' );
	}

	/**
	 * save_form_export
	 *
	 * @param  array $form Gravity Forms form array
	 * @param  bool $is_new True if this is a new form being created. False if this is an existing form being updated.
	 * @return void
	 */
	public function save_form_export( $form, $is_new = false )
	{
		//create an export of this form
		$forms = GFExport::prepare_forms_for_export( array( $form ) );
		$forms['version'] = GFForms::$version;

		//touch the forms before they're saved with this filter
		$forms = apply_filters( 'gravityforms_local_json_save_form', $forms );

		//get path where we are saving .json files, {form_id}.json
		$save_path = self::json_file_path( $form['id'] );

		//write the file
		file_put_contents( $save_path, json_encode( $forms ) );
	}

	public function save_form_export_after_status_change( $form_id )
	{
		if( ! class_exists( 'GFAPI' ) )
		{
			return;
		}
		$this->save_form_export( GFAPI::get_form( $form_id ) );
	}
}
