<?php
/**
 * Main class. Loads all the features of the plugin.
 *
 * @package Gravity_Forms_Power_Boost
 */

defined( 'ABSPATH' ) || exit;

use Gravity_Forms\Gravity_Forms\Editor_Button\GF_Editor_Service_Provider;

/**
 * GravityForms_Power_Boost
 */
class GravityForms_Power_Boost {

	const VERSION = '3.1.7';

	/**
	 * Adds a "last entry" column to the array of columns in the dashboard forms
	 * list.
	 *
	 * @param  array $columns An array of table columns in the forms list.
	 * @return array
	 */
	public function add_columns_to_list_table( $columns ) {
		$columns['last_entry'] = esc_html__( 'Last Entry', 'power-boost-for-gravity-forms' );
		return $columns;
	}

	/**
	 * Adds a row action to the forms list that copies a form shortcode to the
	 * clipboard.
	 *
	 * @param  array $form_actions An array of row actions in the forms list.
	 * @param  int   $form_id A form ID.
	 * @return array
	 */
	public function add_copy_shortcode_row_action( $form_actions, $form_id ) {
		$form_actions['shortcode'] = array(
			'label'      => __( 'Copy Shortcode', 'power-boost-for-gravity-forms' ),
			'aria-label' => __( 'Copy this form\'s shortcode to the clipboard', 'power-boost-for-gravity-forms' ),
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

	/**
	 * Adds field IDs near labels while viewing entries.
	 *
	 * @param  string   $content HTML that renders a field when viewing an entry.
	 * @param  GF_Field $field The field object for which we are editing HTML.
	 * @return string
	 */
	public function add_field_ids_when_viewing_entries( $content, $field ) {
		return preg_replace( '/(class="entry\-view\-field\-name">)([^<]+)(<\/td>)/', '$1 ' . $field['id'] . '. $2$3', $content );
	}

	/**
	 * Adds field IDs near labels while editing entries.
	 *
	 * @param  array $form Form object associative array.
	 * @return array
	 */
	public function add_field_ids_when_editing_entries( $form ) {
		// Are we on the edit entry page?
		if ( 'entry_detail_edit' !== GFForms::get_page() ) {
			return $form;
		}

		foreach ( $form['fields'] as &$field ) {

			// Is the field a type that GF core skips? Or a page break?
			if ( in_array( $field->get_input_type(), array( 'captcha', 'html', 'password', 'page' ), true ) ) {
				// Yes.
				continue;
			}

			$prefix = $field['id'] . '. ';

			if ( ! empty( $field['adminLabel'] ) && substr( $field['adminLabel'], 0, strlen( $prefix ) ) !== $prefix ) {
				$field['adminLabel'] = $field['id'] . '. ' . $field['adminLabel'];
			}
			if ( ! empty( $field['label'] ) && substr( $field['label'], 0, strlen( $prefix ) ) !== $prefix ) {
				$field['label'] = $field['id'] . '. ' . $field['label'];
			}
		}
		return $form;
	}

	/**
	 * Adds field IDs near labels while editing forms.
	 *
	 * Thanks to Dario at Gravity Wiz for suggesting I include this snippet. It
	 * pairs well with the other features that add field IDs to the dashboard.
	 *
	 * @see https://github.com/gravitywiz/snippet-library/blob/master/experimental/gw-field-ids-in-editor-labels.php
	 *
	 * @param  string   $content HTML that renders a field in the form editor.
	 * @param  GF_Field $field A field object for which we are editing HTML.
	 * @return string
	 */
	public function add_field_ids_when_editing_forms( $content, $field ) {
		if ( ! GFCommon::is_form_editor() ) {
			return $content;
		}

		// Is Compact View enabled?
		if ( class_exists( 'GF_Editor_Service_Provider' ) && GF_Editor_Service_Provider::is_compact_view_enabled( get_current_user_id(), strval( $field->formId ) ) ) {
			// Yes. Showing IDs is already a feature provided in Compact View.
			return $content;
		}

		$replace = sprintf( '\0 <span class="gw-inline-field-id">ID: %d</span>', $field->id );
		return preg_replace( '/<\/label>|<\/legend>/', $replace, $content, 1 );
	}

	/**
	 * Adds hooks that power the plugin
	 *
	 * @return void
	 */
	public function add_hooks() {
		// Add compatibility with language packs.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Add columns to the table that lists Forms on edit.php.
		add_filter( 'gform_form_list_columns', array( $this, 'add_columns_to_list_table' ) );

		// Populate the columns we added to the list table.
		add_action( 'gform_form_list_column_last_entry', array( $this, 'populate_columns_we_added' ), 10, 1 );

		// Add a "Copy Shortcode" row action to the forms list.
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

		// When viewing entries, put field IDs near labels.
		add_filter( 'gform_field_content', array( $this, 'add_field_ids_when_viewing_entries' ), 10, 2 );
		// When editing forms, put field IDs near labels.
		add_filter( 'gform_field_content', array( $this, 'add_field_ids_when_editing_forms' ), 10, 2 );
		// When editing entries, put field IDs near labels.
		add_filter( 'gform_admin_pre_render', array( $this, 'add_field_ids_when_editing_entries' ) );

		/**
		 * Add a "Resend Feeds" button near the "Resend Notifications" button
		 * when viewing a single entry.
		 */
		add_filter( 'gform_entry_detail_meta_boxes', array( $this, 'add_feeds_metabox' ), 10, 1 );
		add_action( 'wp_ajax_resend_feeds', array( $this, 'ajax_callback_resend_feeds' ) );

		// Load the class file and initialize the Form Replacer.
		require_once dirname( GF_POWER_BOOST_PLUGIN_ROOT ) . '/includes/class-gfpb-form-replacer.php';
		$replacer = new GFPB_Form_Replacer();
		$replacer->add_hooks();

		// Load the class file and initialize the Local JSON.
		require_once dirname( GF_POWER_BOOST_PLUGIN_ROOT ) . '/includes/class-gfpb-local-json.php';
		$local_json = new GFPB_Local_JSON();
		$local_json->add_hooks();

		// Allow merge tags in HTML fields.
		require_once dirname( GF_POWER_BOOST_PLUGIN_ROOT ) . '/includes/class-gfpb-html-field-merge-tags.php';
		$merge_tags = new GFPB_HTML_Field_Merge_Tags();
		$merge_tags->add_hooks();

		// Replace the dashboard widget with a version that uses caching.
		require_once dirname( GF_POWER_BOOST_PLUGIN_ROOT ) . '/includes/class-gfpb-form-summary-cacher.php';
		$replacer = new GFPB_Form_Summary_Cacher();
		$replacer->add_hooks();
	}

	/**
	 * Adds a Feeds metabox when viewing an entry in the dashboard.
	 *
	 * @param  array $meta_boxes An array of meta boxes to show when viewing entries in the dashboard.
	 * @return array
	 */
	public function add_feeds_metabox( $meta_boxes ) {
		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			return $meta_boxes;
		}

		$meta_boxes['feeds'] = array(
			'title'    => esc_html__( 'Feeds', 'power-boost-for-gravity-forms' ),
			'callback' => array( __CLASS__, 'meta_box_feeds' ),
			'context'  => 'side',
		);
		return $meta_boxes;
	}

	/**
	 * AJAX callback method. Handles the resend feeds button press.
	 *
	 * @return void
	 */
	public function ajax_callback_resend_feeds() {
		check_admin_referer( 'gf_resend_feeds', 'gf_resend_feeds' );

		$form_id  = absint( rgpost( 'form_id' ) );
		$entry_id = absint( rgpost( 'entry_id' ) );
		$feed_ids = json_decode( rgpost( 'feed_ids' ) );

		// Get instances of all add-ons, we don't know which one we need yet.
		$addon_instances = array();
		$addons          = GFAddOn::get_registered_addons();
		foreach ( $addons as $addon ) {
			$a                 = call_user_func( array( $addon, 'get_instance' ) );
			$addon_instances[] = $a;
		}

		// Keep track of sent feeds to report how many ran.
		$sent_feeds = 0;

		$feeds = GFAPI::get_feeds( null, $form_id, null ); // defaults to active feeds only.
		foreach ( $feeds as $feed ) {
			if ( ! in_array( $feed['id'], $feed_ids, true ) ) {
				continue;
			}

			// find the right add-on.
			foreach ( $addon_instances as $instance ) {
				if ( $instance->get_slug() !== $feed['addon_slug'] ) {
					continue;
				}

				/* Disabling the background processing for the feed. */
				$cur_feed = $feed;
				$method   = __METHOD__ . '()';
				add_filter(
					'gform_is_feed_asynchronous',
					function ( $is_asynchronous, $feed, $form, $entry ) use ( $cur_feed, $method ) {
						$log_preamble = $method . ' - ' . current_filter() . ': ';
						GFCommon::log_debug( $log_preamble . __( 'running', 'power-boost-for-gravity-forms' ) );
						if ( $feed['id'] === $cur_feed['id'] ) {
							GFCommon::log_debug( $log_preamble . __( 'Disabling background processing for feed: ', 'power-boost-for-gravity-forms' ) . $feed['id'] );
							$is_asynchronous = false;
						}
						return $is_asynchronous;
					},
					10,
					4
				);

				$instance->maybe_process_feed( GFAPI::get_entry( $entry_id ), GFAPI::get_form( $form_id ) );
				$sent_feeds++;
				break; // we found it, no need to keep looking at add-on instances.
			}
		}

		// Did all the feeds run?
		if ( count( $feed_ids ) !== $sent_feeds ) {
			// No.
			wp_send_json_error(
				sprintf(
					'%s/%s %s',
					$sent_feeds,
					count( $feed_ids ),
					__( 'of feeds were sent', 'power-boost-for-gravity-forms' )
				)
			);
		}

		wp_send_json_success();
	}

	/**
	 * Outputs the Resend Feeds metabox HTML.
	 *
	 * @param  array $args An array of form and entry attributes provided to metaboxes.
	 * @param  array $metabox An array containing the meta box attributes.
	 * @return void
	 */
	public static function meta_box_feeds( $args, $metabox ) {
		$form_id = $args['form']['id'];

		if ( ! GFCommon::current_user_can_any( 'gravityforms_edit_entry_notes' ) ) {
			return;
		}
		?><div class="message" style="display:none;"></div>
		<div>
			<?php

			$feeds = GFAPI::get_feeds( null, $form_id, null ); // defaults to active feeds only.

			if ( empty( $feeds ) || is_wp_error( $feeds ) ) {
				?>
				<p class="description"><?php esc_html_e( 'This form has no active feeds.', 'power-boost-for-gravity-forms' ); ?></p>
				<?php
			} else {
				foreach ( $feeds as $feed ) {
					$feed_name = '';
					if ( ! empty( rgars( $feed, 'meta/feed_name' ) ) ) {
						$feed_name = rgars( $feed, 'meta/feed_name' );
					} elseif ( ! empty( rgars( $feed, 'meta/feedName' ) ) ) {
						$feed_name = rgars( $feed, 'meta/feedName' );
					} elseif ( 'gravityformspartialentries' === rgar( $feed, 'addon_slug' ) ) {
						// Partial Entries add-on does not use feed names.
						$feed_name = __( 'Partial Entries', 'power-boost-for-gravity-forms' );
					} else {
						$feed_name = __( 'Unnamed: ', 'power-boost-for-gravity-forms' ) . rgar( $feed, 'addon_slug' );
					}
					?>
					<input type="checkbox" class="gform_feeds" value="<?php echo esc_attr( $feed['id'] ); ?>" id="feed_<?php echo esc_attr( $feed['id'] ); ?>" />
					<label for="feed_<?php echo esc_attr( $feed['id'] ); ?>"><?php echo esc_html( $feed_name ); ?></label>
					<br /><br />
					<?php
				}
				?>
				<input type="button" name="feeds_resend" value="<?php esc_attr_e( 'Resend Feeds', 'power-boost-for-gravity-forms' ); ?>" class="button" style="" onclick="gfpb_resend_feeds();" />
				<span id="please_wait_container_feeds" style="display:none; margin-left: 5px;">
					<i class='gficon-gravityforms-spinner-icon gficon-spin'></i> <?php esc_html_e( 'Resending...', 'power-boost-for-gravity-forms' ); ?>
				</span>
				<script type="text/javascript">
				<!--
					function gfpb_resend_feeds()
					{
						var entry_id = <?php echo esc_js( $args['entry']['id'] ); ?>;
						var form_id = <?php echo esc_js( $form_id ); ?>;

						var checked_feeds = new Array();
						jQuery(".gform_feeds:checked").each(function () {
							checked_feeds.push(jQuery(this).val());
						});

						if (checked_feeds.length <= 0) {
							displayMessage(<?php echo wp_json_encode( __( 'You must select at least one feed to resend.', 'power-boost-for-gravity-forms' ) ); ?>, 'error', '#feeds');
							return;
						}

						jQuery('#please_wait_container_feeds').fadeIn();

						jQuery.post(ajaxurl, {
								action         : 'resend_feeds',
								gf_resend_feeds: <?php echo wp_json_encode( wp_create_nonce( 'gf_resend_feeds' ) ); ?>,
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
									displayMessage(<?php echo wp_json_encode( esc_html__( 'Feeds were resent successfully.', 'power-boost-for-gravity-forms' ) ); ?>, "success", "#feeds" );

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
		</div>
		<?php
	}

		/**
		 * Adds a "Settings" item to the Admin bar that leads to the global
		 * settings.
		 *
		 * @return void
		 */
	public function admin_bar_add_settings_link() {
		if ( ! class_exists( 'GFCommon' )
		|| ! GFCommon::current_user_can_any( 'gravityforms_view_settings' ) ) {
			return;
		}

		global $wp_admin_bar;
		$wp_admin_bar->add_node(
			array(
				'id'     => 'gform-forms-view-settings',
				'parent' => 'gform-forms',
				'title'  => esc_html__( 'Settings', 'power-boost-for-gravity-forms' ),
				'href'   => admin_url( 'admin.php?page=gf_settings' ),
			)
		);
	}

	/**
	 * Loads translated strings.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'power-boost-for-gravity-forms', false, dirname( GF_POWER_BOOST_PLUGIN_ROOT ) . '/languages' );
	}

	/**
	 * Populates the "Last Entry" forms list column we added.
	 *
	 * @param  stdClass $item A class that resembles a form object.
	 * @return void
	 */
	public function populate_columns_we_added( $item ) {
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

		if ( empty( $item->entry_count ) ) {
			echo '-';
			return;
		}

		$sorting = array(
			'key'        => 'date_created',
			'direction'  => 'DESC',
			'is_numeric' => false,
		);

		// Page size 1 is how we only get one entry.
		$paging = array(
			'offset'    => 0,
			'page_size' => 1,
		);

		$form_id = $item->id;
		$entries = GFAPI::get_entries( $form_id, array(), $sorting, $paging );
		if ( empty( $entries ) ) {
			echo '-';
		}

		$value = GFCommon::format_date( rgar( $entries[0], 'date_created' ), false );

		$url = admin_url(
			sprintf(
				'admin.php?page=gf_entries&view=entry&id=%s&lid=%s',
				$form_id,
				rgar( $entries[0], 'id' )
			)
		);

		printf(
			'<a href="%s">%s</a>',
			esc_attr( $url ),
			esc_html( $value )
		);
	}

		/**
		 * Callback method for admin_enqueue_scripts. Enqueues a stylesheet to
		 * change the way the dashboard appears.
		 *
		 * @return void
		 */
	public function dashboard_includes() {
		if ( ! class_exists( 'GFCommon' ) ) {
			return;
		}
		wp_enqueue_style(
			'gfpb-dashboard',
			plugins_url( 'dashboard.min.css', GF_POWER_BOOST_PLUGIN_ROOT ),
			array(),
			self::VERSION
		);
		wp_enqueue_script(
			'gfpb-dashboard',
			plugins_url( 'dashboard.min.js', GF_POWER_BOOST_PLUGIN_ROOT ),
			array(),
			self::VERSION,
			true
		);
	}
}
