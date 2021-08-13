<?php
defined( 'ABSPATH' ) or exit;

/**
 * Gravity_Forms_Power_Boost_Form_Replacer
 * 
 * This class adds a tab to the Gravity Forms Import/Export page found at Forms
 * > Import/Export in the dashboard. The tab is named "Replace Forms" and works
 * similarly to "Import Forms" except that it updates existing forms based on ID
 * rather than always inserting duplicates.
 */
class Gravity_Forms_Power_Boost_Form_Replacer
{
	const EXPORT_TAB_SLUG = 'gforms_replacer';
	
	/**
	 * add_hooks
	 *
	 * @return void
	 */
	public function add_hooks()
	{
		//Adds an item to the Import/Export menu tabs
		add_filter( 'gform_export_menu', array( $this, 'add_import_tab' ) );

		//Populate the tab with content
		add_action( 'gform_export_page_' . self::EXPORT_TAB_SLUG, array( $this, 'populate_import_tab' ) );
	}
	
	/**
	 * add_import_tab
	 *
	 * @param  array $setting_tabs
	 * @return array
	 */
	public function add_import_tab( $setting_tabs )
	{
		if( ! class_exists( 'GFCommon') || ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) )
		{
			return;
		}

		$setting_tabs[] = array(
			'name'  => self::EXPORT_TAB_SLUG,
			'label' => __( 'Replace Forms', 'gravityforms-power-boost' ),
		);	 
		return $setting_tabs;
	}
	
	/**
	 * populate_import_tab
	 *
	 * @return void
	 */
	public function populate_import_tab()
	{
		if( ! class_exists( 'GFCommon') || ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) )
		{
			wp_die( 'You do not have permission to access this page' );
		}

		if ( isset( $_POST['import_forms'] ) ) {

			check_admin_referer( 'gf_replace_forms', 'gf_replace_forms_nonce' );

			if ( ! empty( $_FILES['gf_import_file']['tmp_name'][0] ) ) {

				// Set initial count to 0.
				$count = 0;

				// Loop through each uploaded file.
				foreach ( $_FILES['gf_import_file']['tmp_name'] as $import_file ) {
					$count += self::update_forms( $import_file );
				}

				if ( $count == 0 ) {
					$error_message = sprintf(
						esc_html__( 'Forms could not be imported. Please make sure your files have the .json extension, and that they were generated by the %sGravity Forms Export form%s tool.', 'gravityforms' ),
						'<a href="admin.php?page=gf_export&view=export_form">',
						'</a>'
					);
					GFCommon::add_error_message( $error_message );
				} else if ( $count == '-1' ) {
					GFCommon::add_error_message( esc_html__( 'Forms could not be imported. Your export file is not compatible with your current version of Gravity Forms.', 'gravityforms' ) );
				} else {
					$form_text = $count > 1 ? esc_html__( 'forms', 'gravityforms' ) : esc_html__( 'form', 'gravityforms' );
					$edit_link = $count == 1 ? "<a href='admin.php?page=gf_edit_forms&id={$forms[0]['id']}'>" . esc_html__( 'Edit Form', 'gravityforms' ) . '</a>' : '';
					GFCommon::add_message( sprintf( esc_html__( 'Gravity Forms imported %d %s successfully', 'gravityforms' ), $count, $form_text ) . ". $edit_link" );
				}
			}
		}

		GFExport::page_header();

		?><div class="gform-settings__content">
			<form method="post" enctype="multipart/form-data" class="gform_settings_form">
				<?php wp_nonce_field( 'gf_replace_forms', 'gf_replace_forms_nonce' ); ?>
				<div class="gform-settings-panel gform-settings-panel--full">
					<header class="gform-settings-panel__header"><legend class="gform-settings-panel__title"><?php 
					
					esc_html_e( 'Replace Forms', 'gravityforms-power-boost' );
					
					?></legend></header>
					<div class="gform-settings-panel__content">
						<div class="gform-settings-description"><?php
							printf(
								esc_html__( 'Select the Gravity Forms export files you would like to import. Please make sure your files have the .json extension, and that they were generated by the %sGravity Forms Export form%s tool. When you click the import button below, Gravity Forms will import the forms.', 'gravityforms-power-boost' ),
								'<a href="admin.php?page=gf_export&view=export_form">',
								'</a>'
							);
						?><p><b><?php 
							printf(
								esc_html__( 'This feature updates existing forms instead of creating duplicates like the Import Forms tab. Provided by %sPower Boost for Gravity Forms%s.', 'gravityforms-power-boost' ),
								'<a href="https://wordpress.org/plugins/power-boost-for-gravity-forms/">',
								'</a>'
							); 
						?></b></p></div>
						<table class="form-table">
							<tr valign="top">
								<th scope="row">
									<label for="gf_import_file"><?php esc_html_e( 'Select Files', 'gravityforms-power-boost' ); ?></label> <?php gform_tooltip( 'import_select_file' ) ?>
								</th>
								<td><input type="file" name="gf_import_file[]" id="gf_import_file" multiple /></td>
							</tr>
						</table>
						<br /><br />
						<input type="submit" value="<?php esc_html_e( 'Import', 'gravityforms-power-boost' ) ?>" name="import_forms" class="button large primary" />
					</div>
				</div>
			</form>
		</div><?php

		GFExport::page_footer();
	}
	
	/**
	 * update_forms
	 *
	 * @param  string $import_file_path
	 * @return int The number of forms that were updated
	 */
	public static function update_forms( $import_file_path )
	{
		if( ! class_exists( 'GFAPI' ) )
		{
			return 0;	
		}

		$json = file_get_contents( $import_file_path );
		if( false === $json )
		{
			return 0;
		}

		//Deserialize into an array of forms
		$forms_array = json_decode( $json, true );

		//Update the forms
		$updated_count = 0;
		foreach( $forms_array as $form )
		{
			if( empty( $form['id'] ) )
			{
				continue;
			}
			GFAPI::update_form( $form, $form['id'] );
			$updated_count++;
		}
		return $updated_count;
	}	
}
