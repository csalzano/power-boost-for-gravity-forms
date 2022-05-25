<?php
defined( 'ABSPATH' ) or exit;

/**
 * Plugin Name: Power Boost for Gravity Forms
 * Plugin URI: https://entriestogooglesheet.com/gravity-forms-power-boost
 * Description: Enhances the dashboard for Gravity Forms power users.
 * Author: Breakfast Co.
 * Author URI: https://breakfastco.xyz
 * Version: 2.3.0
 * Text Domain: gravityforms-power-boost
 * License: GPLv2 or later
 */

if( ! defined( 'GF_POWER_BOOST_PLUGIN_ROOT' ) )
{
	define( 'GF_POWER_BOOST_PLUGIN_ROOT', __FILE__ );
}

class Gravity_Forms_Power_Boost
{
	const VERSION = '2.3.0';

	var $rendered_form_ids;

	public function add_columns_to_list_table( $columns )
	{
		$columns['last_entry'] = esc_html__( 'Last Entry', 'gravityforms-power-boost' );
		return $columns;
	}

	public function add_copy_shortcode_row_action( $form_actions, $form_id )
	{
		$form_actions['shortcode'] = array(
			'label'      => __( 'Copy Shortcode', 'gravityforms-power-boost' ),
			'aria-label' => __( 'Copy this form\'s shortcode to the clipboard', 'gravityforms-power-boost' ),
			'url'        => '#',
			'menu_class' => 'gf_form_toolbar_settings',
			'link_class' => '',
			'onclick'    => sprintf( 
				'powerBoostClipboardCopy( \'[gravityform id="%s" title="false" description="false" ajax="true"]\' );', 
				$form_id
			),
		);
		return $form_actions;
	}

	public function add_field_ids_when_viewing_entries( $content, $field, $value, $entry_id, $form_id )
	{
		return preg_replace( '/(class="entry\-view\-field\-name">)([^<]+)(<\/td>)/', '$1 ' . $field['id'] . '. $2$3', $content );
	}

	public function add_field_ids_when_editing_entries( $form )
	{
		//make sure we're on the edit entry page
		if( empty( $_POST['screen_mode'] ) || 'edit' !== $_POST['screen_mode'] )
		{
			return $form;
		}

		foreach( $form['fields'] as &$field )
		{
			if( ! empty( $field['adminLabel'] ) )
			{
				$field['adminLabel'] = $field['id'] . '. ' . $field['adminLabel'];
			}
			$field['label'] = $field['id'] . '. ' . $field['label'];
		}
		return $form;
	}

	/**
	 * add_field_ids_when_editing_forms
	 *
	 * Adds form IDs near field labels while editing forms.
	 *
	 * Thanks to Dario at Gravity Wiz for suggesting I include this snippet. It
	 * pairs well with the other features that add field IDs to the dashboard.
	 *
	 * @see https://github.com/gravitywiz/snippet-library/blob/master/experimental/gw-field-ids-in-editor-labels.php
	 *
	 * @param  string $content
	 * @param  GF_Field $field
	 * @return string
	 */
	public function add_field_ids_when_editing_forms( $content, $field )
	{
		if ( ! GFCommon::is_form_editor() )
		{
			return $content;
		}
		$replace = sprintf( '\0 <span class="gw-inline-field-id">ID: %d</span>', $field->id );
		return preg_replace( "/<\/label>|<\/legend>/", $replace, $content, 1 );
	}

	public function add_hooks()
	{
		//Add columns to the table that lists Forms on edit.php
		add_filter( 'gform_form_list_columns', array( $this, 'add_columns_to_list_table' ) );

		//Populate the columns we added to the list table
		add_action( 'gform_form_list_column_last_entry', array( $this, 'populate_columns_we_added' ), 10, 1 );

		//Add a "Copy Shortcode" row action to the forms list
		add_filter( 'gform_form_actions', array( $this, 'add_copy_shortcode_row_action' ), 10, 2 );

		/**
		 * Change the Forms menu of the admin bar
		 */
		/**
		 * Add a link to the global Gravity Forms Settings page
		 */
		add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar_add_settings_link' ), 100 );



		/**
		 * Include a stylesheet to help display form IDs in the form editor and
		 * JavaScript for the Copy Shortcode row action in the forms list.
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'dashboard_includes' ) );

		//When viewing entries, put field IDs near labels
		add_filter( 'gform_field_content', array( $this, 'add_field_ids_when_viewing_entries' ), 10, 5 );
		//When editing forms, put field IDs near labels
		add_filter( 'gform_field_content', array( $this, 'add_field_ids_when_editing_forms' ), 10, 2 );
		//When editing entries, put field IDs near labels
		add_filter( 'gform_admin_pre_render', array( $this, 'add_field_ids_when_editing_entries' ) );

		/**
		 * Add a "Resend Feeds" button near the "Resend Notifications" button
		 * when viewing a single entry.
		 */
		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'add_feeds_metabox' ), 10, 3 );
		add_action( 'wp_ajax_resend_feeds', array( $this, 'ajax_callback_resend_feeds' ) );

		//Load the class file and initialize the Form Replacer
		require_once( __DIR__ . '/includes/form-replacer.php' );
		$replacer = new Gravity_Forms_Power_Boost_Form_Replacer();
		$replacer->add_hooks();

		//Load the class file and initialize the Local JSON
		require_once( __DIR__ . '/includes/local-json.php' );
		$local_json = new Gravity_Forms_Local_JSON();
		$local_json->add_hooks();
	}

	public function add_feeds_metabox( $meta_boxes, $entry, $form )
	{
		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) )
		{
			return $meta_boxes;
		}

		$meta_boxes['feeds'] = array(
			'title'    => esc_html__( 'Feeds', 'gravityforms-power-boost' ),
			'callback' => array( __CLASS__, 'meta_box_feeds' ),
			'context'  => 'side',
		);
		return $meta_boxes;
	}

	public function ajax_callback_resend_feeds()
	{
		check_admin_referer( 'gf_resend_feeds', 'gf_resend_feeds' );

		$form_id = absint( rgpost( 'form_id' ) );
		$entry_id = absint( rgpost( 'entry_id' ) );
		$feed_ids = json_decode( rgpost( 'feed_ids' ) );

		//Get instances of all add-ons, we don't know which one we need yet
		$addon_instances = array();
		$addons = GFAddOn::get_registered_addons();
		foreach ( $addons as $addon ) {
			$a = call_user_func( array( $addon, 'get_instance' ) );
			$addon_instances[] = $a;
		}

		//Keep track of sent feeds to report how many ran
		$sent_feeds = 0;

		$feeds = GFAPI::get_feeds( null, $form_id, null ); //defaults to active feeds only
		foreach( $feeds as $feed )
		{
			if( ! in_array( $feed['id'], $feed_ids ) )
			{
				continue;
			}

			//find the right add-on
			foreach( $addon_instances as $instance )
			{
				if( $instance->get_slug() != $feed['addon_slug'] )
				{
					continue;
				}

				/* Disabling the background processing for the feed. */
				$curFeed = $feed;
				add_filter('gform_is_feed_asynchronous', function ($is_asynchronous, $feed, $form, $entry) use ($curFeed) {
					GFCommon::log_debug('Gravity_Forms_Power_Boost::ajax_callback_resend_feeds() - gform_is_feed_asynchronous: running.');
					$feed_name  = rgars($feed, 'meta/feedName');
					// Run only for this feed name.
					if ($feed_name === rgars($curFeed, 'meta/feedName')) {
						GFCommon::log_debug('Gravity_Forms_Power_Boost::ajax_callback_resend_feeds() - gform_is_feed_asynchronous: Disabling background processing for feed: ' . $feed_name);
						$is_asynchronous = false;
					}
					return $is_asynchronous;
				}, 10, 4);

				$instance->maybe_process_feed( GFAPI::get_entry( $entry_id ), GFAPI::get_form( $form_id ) );
				$sent_feeds++;
				break; //we found it, no need to keep looking at add-on instances
			}
		}

		//Did all the feeds run?
		if( $sent_feeds != sizeof( $feed_ids ) )
		{
			//No
			wp_send_json_error( sprintf(
				'%s/%s %s',
				$sent_feeds,
				sizeof( $feed_ids ),
				__( 'of feeds were sent', 'gravityforms-power-boost' )
			) );
		}

		wp_send_json_success();
	}

	public static function meta_box_feeds( $args, $metabox )
	{
		$form    = $args['form'];
		$form_id = $form['id'];

		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) )
		{
			return;
		}
		?><div class="message" style="display:none;"></div>
		<div>
			<?php

			$feeds = GFAPI::get_feeds( null, $form_id, null ); //defaults to active feeds only

			if( empty( $feeds ) || is_wp_error( $feeds ) )
			{
				?>
				<p class="description"><?php esc_html_e( 'This form has no active feeds.', 'gravityforms-power-boost' ); ?></p>
				<?php
			}
			else
			{
				foreach( $feeds as $feed )
				{
					?>
					<input type="checkbox" class="gform_feeds" value="<?php echo esc_attr( $feed['id'] ); ?>" id="feed_<?php echo esc_attr( $feed['id'] ); ?>" />
					<label for="feed_<?php echo esc_attr( $feed['id'] ); ?>"><?php echo esc_html( $feed['meta']['feed_name'] ); ?></label>
					<br /><br />
					<?php
				}
				?>
				<input type="button" name="feeds_resend" value="<?php esc_attr_e( 'Resend Feeds', 'gravityforms-power-boost' ) ?>" class="button" style="" onclick="gfpb_resend_feeds();" />
				<span id="please_wait_container_feeds" style="display:none; margin-left: 5px;">
					<i class='gficon-gravityforms-spinner-icon gficon-spin'></i> <?php esc_html_e( 'Resending...', 'gravityforms-power-boost' ); ?>
				</span>
				<script type="text/javascript">
				<!--
					function gfpb_resend_feeds()
					{
						var entry_id = <?php echo $args['entry']['id']; ?>;
						var form_id = <?php echo $form_id; ?>;

						var checked_feeds = new Array();
						jQuery(".gform_feeds:checked").each(function () {
							checked_feeds.push(jQuery(this).val());
						});

						if (checked_feeds.length <= 0) {
							displayMessage(<?php echo json_encode( __( 'You must select at least one feed to resend.', 'gravityforms-power-boost' ) ); ?>, 'error', '#feeds');
							return;
						}

						jQuery('#please_wait_container_feeds').fadeIn();

						jQuery.post(ajaxurl, {
								action         : 'resend_feeds',
								gf_resend_feeds: '<?php echo wp_create_nonce( 'gf_resend_feeds' ); ?>',
								feed_ids       : jQuery.toJSON(checked_feeds),
								entry_id       : '<?php echo absint( $args['entry']['id'] ); ?>',
								form_id        : '<?php echo absint( $form_id ); ?>'
							},
							function (response) {
								if( ! response.success )
								{
									displayMessage( response.data, "error", "#feeds");
								}
								else
								{
									displayMessage(<?php echo json_encode( esc_html__( 'Feeds were resent successfully.', 'gravityforms-power-boost' ) ); ?>, "updated", "#feeds" );

									// reset UI
									jQuery(".gform_feeds").attr( 'checked', false );
								}

								jQuery('#please_wait_container_feeds').hide();
								setTimeout(function () {
									jQuery('#feeds_container').find('.message').slideUp();
								}, 5000);
							}
						);
					}
				-->
				</script>
				<?php
			}
			?>
		</div><?php
	}

	public function admin_bar_add_settings_link()
	{
		if( ! class_exists( 'GFCommon' ) 
			|| ! GFCommon::current_user_can_any( 'gravityforms_view_settings' ) )
		{
			return;
		}

		global $wp_admin_bar;
		$wp_admin_bar->add_node(
			array(
				'id'     => 'gform-forms-view-settings',
				'parent' => 'gform-forms',
				'title'  => esc_html__( 'Settings', 'gravityforms-power-boost' ),
				'href'   => admin_url( 'admin.php?page=gf_settings' ),
			)
		);
	}

	/**
	 * populate_columns_we_added
	 *
	 * @param  stdClass $item Almost a Gravity Form object
	 * @return void
	 */
	public function populate_columns_we_added( $item )
	{
		/*
		
		$item is a stdClass object that's almost a Form object

		$item = {
			id:"28"
			title:"(New) Estimator Tool"
			date_created:"2017-11-17 14:22:55"
			is_active:"0"
			entry_count:"0"
			view_count:"0"
		}

		*/

		if( empty( $item->entry_count ) )
		{
			echo '-';
			return;
		}

		$sorting = array(
			'key'        => 'date_created',
			'direction'  => 'DESC',
			'is_numeric' => false
		);

		//Page size 1 is how we only get one entry
		$paging = array(
			'offset'    => 0,
			'page_size' => 1
		);

		$form_id = $item->id;
		$entries = GFAPI::get_entries( $form_id, array(), $sorting, $paging );
		if( empty( $entries ) )
		{
			echo '-';
		}

		$value = GFCommon::format_date( rgar( $entries[0], 'date_created' ), false );

		$url = admin_url( sprintf( 
			'admin.php?page=gf_entries&view=entry&id=%s&lid=%s',
			$form_id,
			rgar( $entries[0], 'id' )
		) );

		printf( 
			'<a href="%s">%s</a>',
			$url,
			$value
		);
	}
	
	/**
	 * dashboard_includes
	 * 
	 * Callback method for admin_enqueue_scripts. Enqueues a stylesheet to 
	 * change the way the dashboard appears.
	 *
	 * @return void
	 */
	public function dashboard_includes()
	{
		if( ! class_exists( 'GFCommon' ) )
		{
			return;
		}
		wp_enqueue_style( 
			'gfpb-dashboard',
			plugins_url( 'dashboard.min.css', __FILE__ ),
			[],
			self::VERSION
		);
		wp_enqueue_script( 
			'gfpb-dashboard',
			plugins_url( 'dashboard.min.js', __FILE__ ),
			[],
			self::VERSION
		);
	}
}
$power_boost_9000 = new Gravity_Forms_Power_Boost();
$power_boost_9000->add_hooks();
