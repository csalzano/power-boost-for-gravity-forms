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
class Gravity_Forms_Power_Boost_HTML_Field_Merge_Tags
{
	public function add_hooks()
	{
		/**
		 * Allow HTML field contents to use merge tags to insert field values
		 * from previous pages. This makes it easy to create a final page with a
		 * "Please Review Your Entry" HTML field that shows the user critical
		 * values they entered on previous pages.
		 */
		add_filter( 'gform_field_content', array( $this, 'enable_merge_tags_in_html_fields' ), 10, 5 );
	}

	/**
	 * @param string $field_content The field content to be filtered.
	 * @param GF_Field $field The field that this input tag applies to.
	 * @param string $value The default/initial value that the field should be pre-populated with.
	 * @param integer $entry_id When executed from the entry detail screen, $lead_id will be populated with the Entry ID. Otherwise, it will be 0.
	 * @param integer $form_id The current Form ID.
	 */
	public function enable_merge_tags_in_html_fields( $field_content, $field, $value, $entry_id, $form_id )
	{
		//If Gravity Forms is not running, do nothing
		if( ! class_exists( 'GFAPI' ) )
		{
			return $field_content;
		}

		if( empty( $field_content ) || 'html' != $field->type )
		{
			return $field_content;
		}

		$form = GFAPI::get_form( $form_id );

		/**
		 * $lead holds the values entered on previous pages for a current form
		 * entry or after a Save & Continue link was clicked
		 */
		$lead = $this->get_partial_entry( $form );
		/**
		 * Send false as the last argument, $nl2br, false. Do not convert line
		 * breaks to <br /> elements.
		 */
		return GFCommon::replace_variables( $field_content, $form, $lead, false, true, false );
	}

	/**
	 * Retrieves partial entry info in two ways:
	 * If the "Save and Continue" setting is enabled in the form's settings or
	 * previous pages have been submitted and those values are stored in $_POST.
	 *
	 * @return Array The partial_entry member of the array returned by GFFormsModel::get_draft_submission_values( $resume_token )
	 */
	protected function get_partial_entry( $form )
	{
		//Has the user clicked a Save & Continue link?
		$resume_token = rgpost( 'gform_resume_token' );
		if( empty( $resume_token ) )
		{
			$resume_token = rgget( 'gf_token' );
		}
		$resume_token = sanitize_key( $resume_token );

		if( ! empty( $resume_token ) )
		{
			//Yes, the user clicked a Save & Continue link, Gravity Forms has the values
			$incomplete_submission_info = GFFormsModel::get_draft_submission_values( $resume_token );
			if ( ! empty( $incomplete_submission_info['form_id'] ) && $incomplete_submission_info['form_id'] == $form['id'] )
			{
				$submission_details_json = $incomplete_submission_info['submission'];
				$submission_details      = json_decode( $submission_details_json, true );
				return $submission_details['partial_entry'];
			}
		}

		/**
		 * The user did not click a Save & Continue link. Perhaps the user is
		 * filling out the form right now, and the values are in $_POST.
		 * Use values in $_POST to replicate a piece of the array that is
		 * returned by GFFormsModel::get_draft_submission_values()
		 */
		$partial_entry = array(
			'id'           => null,
			'post_id'      => null,
			'date_created' => null,
			'date_updated' => null,
			'form_id'      => $form['id'],
			'ip'           => $_SERVER['REMOTE_ADDR'],
			'source_url'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
			'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
			'created_by'   => null,
			'currency'     => GFCommon::get_currency(),
		);

		/**
		 * Populate field values into $partial_entry. These values are what will
		 * trick Gravity Forms into making field merge tags evaluate.
		 * @see https://docs.gravityforms.com/field-merge-tags/
		 */
		foreach( $form['fields'] as $field )
		{
			$inputs = $field->get_entry_inputs();
			if ( is_array( $inputs ) ) {
				foreach ( $inputs as $input ) {
					$partial_entry[$input['id']] = rgpost( 'input_' . str_replace( '.', '_', $input['id'] ) );
				}
			} else {
				$partial_entry[str_replace( '_', '.', $field->id )] = rgpost( 'input_' . $field->id );
			}
		}
		return $partial_entry;
	}
}