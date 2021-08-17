<?php
defined( 'ABSPATH' ) or exit;

/**
 * Plugin Name: Power Boost for Gravity Forms
 * Plugin URI: https://entriestogooglesheet.com/gravity-forms-power-boost
 * Description: Enhances the dashboard for Gravity Forms power users.
 * Author: Breakfast Co.
 * Author URI: https://breakfastco.xyz
 * Version: 1.4.0
 * Text Domain: gravityforms-power-boost
 * License: GPLv2 or later
 */

class Gravity_Forms_Power_Boost
{
	var $rendered_form_ids;

	public function add_columns_to_list_table( $columns )
	{
		$columns['last_entry'] = esc_html__( 'Last Entry', 'gravityforms-power-boost' );
		return $columns;
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
			$field['label'] = $field['id'] . '. ' . $field['label'];
		}
		return $form;
	}

	public function add_hooks()
	{
		//Add columns to the table that lists Forms on edit.php
		add_filter( 'gform_form_list_columns', array( $this, 'add_columns_to_list_table' ) );

		//Populate the columns we added to the list table
		add_action( 'gform_form_list_column_last_entry', array( $this, 'populate_columns_we_added' ), 10, 1 );

		//Keep track of all Gravity Forms form IDs that are rendered during this request
		add_filter( 'gform_pre_render', array( $this, 'save_rendered_form_ids' ), 10, 3 );
		
		/**
		 * Change the Forms menu of the admin bar
		 */
		/** Make sure forms embedded on the current page are in the Recent Forms
		 * list, highlighted, and grouped at the top.
		 */
		add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar_add_and_highlight_forms' ), 99 );
		/**
		 * Add a link to the global Gravity Forms Settings page
		 */
		add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar_add_settings_link' ), 100 );
		/** 
		 * Include a style sheet to customize the admin bar
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'admin_bar_include_css' ) );


		/**
		 * Include a stylesheet to better display long form names in the 2.5
		 * dashboard form switcher dropdown.
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'include_css' ) );

		//When viewing entries, put field IDs near labels
		add_filter( 'gform_field_content', array( $this, 'add_field_ids_when_viewing_entries' ), 10, 5 );
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
				$instance->maybe_process_feed( GFAPI::get_entry( $entry_id ), GFAPI::get_form( $form_id ) );
				break; //we found it, no need to keep looking at add-on instances
			}
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
		?><div class="message" style="display:none;padding:10px;"></div>
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
									displayMessage( response, "error", "#feeds");
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
		if( ! GFCommon::current_user_can_any( 'gravityforms_view_settings' ) )
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

	public function admin_bar_include_css()
	{
		if( ! is_admin_bar_showing() )
		{
			return;
		}
		wp_enqueue_style( 'gfpb-admin-bar', plugins_url( 'admin-bar.min.css', __FILE__ ) );
	}
	
	/**
	 * save_rendered_form_ids
	 * 
	 * Hook callback on gform_pre_render. Just before a Gravity Form is 
	 * rendered, save its form ID in a member variable in this class so we know
	 * which forms are on the page.
	 *
	 * @param  mixed $form
	 * @param  mixed $is_ajax
	 * @param  mixed $field_values
	 * @return void
	 */
	public function save_rendered_form_ids( $form, $is_ajax, $field_values )
	{
		if( empty( $form['id'] ) )
		{
			return $form;
		}

		if( ! is_array( $this->rendered_form_ids ) )
		{
			$this->rendered_form_ids = array();
		}
		$this->rendered_form_ids[] = $form['id'];
		return $form;
	}

	public function admin_bar_add_and_highlight_forms()
	{
		//If there are no rendered forms on this page, abort
		if( empty( $this->rendered_form_ids ) )
		{
			return;
		}

		global $wp_admin_bar;

		//Keep track of the forms we find in the list
		$form_ids_found_in_recent_list = array();

		foreach( $wp_admin_bar->get_nodes() as &$node )
		{
			//Is this node a form in the Recent forms menu?
			if( ! empty( $node->parent ) && 'gform-form-recent-forms' == $node->parent )
			{
				$form_id = intval( str_replace( 'gform-form-', '', $node->id ) );
				if( in_array( $form_id, $this->rendered_form_ids ) )
				{
					$form_ids_found_in_recent_list[] = $form_id;
				}
			}
		}

		//Add forms that appear on the page but aren't on the Recent Forms list
		//Are there rendered forms that do not appear in the recent list?
		$form_ids_to_add = array_diff( $this->rendered_form_ids, $form_ids_found_in_recent_list );
		if( ! empty( $form_ids_to_add ) )
		{
			/**
			 * Add rendered forms to the recent forms list so all forms on the page
			 * are in the admin bar Forms list.
			 */
			foreach( $form_ids_to_add as $form_id )
			{
				$form = GFAPI::get_form( $form_id );

				$wp_admin_bar->add_node(
					array(
						'id'     => 'gform-form-' . $form_id,
						'parent' => 'gform-form-recent-forms',
						'title'  => esc_html( $form['title'] ),
						'href'   => GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ? admin_url( 'admin.php?page=gf_edit_forms&id=' . $form_id ) : '',
					)
				);

				if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
					$wp_admin_bar->add_node(
						array(
							'id'     => 'gform-form-' . $form_id . '-edit',
							'parent' => 'gform-form-' . $form_id,
							'title'  => esc_html__( 'Edit', 'gravityforms-power-boost' ),
							'href'   => admin_url( 'admin.php?page=gf_edit_forms&id=' . $form_id ),
						)
					);
				}

				if ( GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
					$wp_admin_bar->add_node(
						array(
							'id'     => 'gform-form-' . $form_id . '-entries',
							'parent' => 'gform-form-' . $form_id,
							'title'  => esc_html__( 'Entries', 'gravityforms-power-boost' ),
							'href'   => admin_url( 'admin.php?page=gf_entries&id=' . $form_id ),
						)
					);
				}

				if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
					$wp_admin_bar->add_node(
						array(
							'id'     => 'gform-form-' . $form_id . '-settings',
							'parent' => 'gform-form-' . $form_id,
							'title'  => esc_html__( 'Settings', 'gravityforms-power-boost' ),
							'href'   => admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=settings&id=' . $form_id ),
						)
					);
				}

				if ( GFCommon::current_user_can_any( array(
					'gravityforms_edit_forms',
					'gravityforms_create_form',
					'gravityforms_preview_forms'
				) )
				) {
					$wp_admin_bar->add_node(
						array(
							'id'     => 'gform-form-' . $form_id . '-preview',
							'parent' => 'gform-form-' . $form_id,
							'title'  => esc_html__( 'Preview', 'gravityforms-power-boost' ),
							'href'   => trailingslashit( site_url() ) . '?gf_page=preview&id=' . $form_id,
						)
					);
				}
			}
		}

		//Hold onto the nodes that we want to end up at the bottom of the list 
		$non_embedded_recent_forms_nodes = array();

		/**
		 * Loop over the admin bar nodes again to change the appearance of forms
		 * that appear on this page.
		 */	
		foreach( $wp_admin_bar->get_nodes() as &$node )
		{
			//Is this node a form in the Recent forms menu?
			if( ! empty( $node->parent ) && 'gform-form-recent-forms' == $node->parent )
			{
				$form_id = intval( str_replace( 'gform-form-', '', $node->id ) );
				if( in_array( $form_id, $this->rendered_form_ids ) )
				{
					//Add a CSS class
					if( ! is_array( $node->meta ) )
					{
						$node->meta = array();
					}
					if( ! isset( $node->meta['class'] ) )
					{
						$node->meta['class'] = '';
					}
					$class = apply_filters( 'gfpb_rendered_form_css_classes', 'gfpb-recent' );
					$node->meta['class'] .= ' ' . $class;
					$node->meta['class'] = trim( $node->meta['class'] );

					//Add an emoji, too, in case the user can't see the color contrast
					$emoji = apply_filters( 'gfpb_rendered_form_emoji', '📌' );
					$node->title = '<span title="Rendered on this page">' . $node->title . ' ' .  $emoji . '</span>';
				}
				//Outside the condition so the whole list is tossed
				$wp_admin_bar->remove_node( $node->id );
				
				if( in_array( $form_id, $this->rendered_form_ids ) )
				{
					$wp_admin_bar->add_node( $node );
				}
				else
				{
					$non_embedded_recent_forms_nodes[] = $node;
				}
			}
		}
		//Put all the Recent Forms that aren't embedded at the bottom
		foreach( $non_embedded_recent_forms_nodes as $node )
		{
			$wp_admin_bar->add_node( $node );
		}
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
	 * include_css
	 * 
	 * Callback method for admin_enqueue_scripts. Enqueues a stylesheet to 
	 * change the way the dashboard appears.
	 *
	 * @return void
	 */
	public function include_css()
	{
		wp_enqueue_style( 'gfpb-dashboard', plugins_url( 'dashboard.min.css', __FILE__ ) );
	}
}
$power_boost_9000 = new Gravity_Forms_Power_Boost();
$power_boost_9000->add_hooks();
