<?php
/**
 * Provides a version of the Gravity Forms dashboard widget that caches database
 * queries.
 *
 * @package Gravity_Forms_Power_Boost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gravity_Forms_Power_Boost_Form_Summary_Cacher
 *
 * This class removes the Gravity Forms dashboard widget and provides a
 * replacement that uses caching. Sites with many forms and entries experience
 * long dashboard load times because of the 3 database queries in the widget.
 */
class GFPB_Form_Summary_Cacher {

	/**
	 * Adds hooks that power the feature.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'add_hooks_init' ), 11 );
	}

	/**
	 * Adds hooks that power the feature on the `init` hook.
	 *
	 * @return void
	 */
	public function add_hooks_init() {
		if ( ! has_action( 'wp_dashboard_setup', array( 'GFForms', 'dashboard_setup' ) ) ) {
			return;
		}
		// Remove the dashboard widget Gravity Forms provides.
		remove_action( 'wp_dashboard_setup', array( 'GFForms', 'dashboard_setup' ) );
		// Add a replacement.
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_setup' ) );
	}

	/**
	 * Registers the dashboard widget.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function dashboard_setup() {
		/**
		 * Changes the dashboard widget title
		 *
		 * @param string $dashboard_title The dashboard widget title.
		 */
		$dashboard_title = apply_filters( 'gform_dashboard_title', __( 'Gravity Forms', 'gravityforms' ) );
		wp_add_dashboard_widget( 'rg_forms_dashboard', $dashboard_title, array( __CLASS__, 'dashboard' ) );
	}

	/**
	 * Displays the dashboard UI.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public static function dashboard() {
		// Make sure Gravity Forms is running.
		if ( ! class_exists( 'GFCommon' ) ) {
			return;
		}
		$forms = self::get_form_summary();

		if ( count( $forms ) > 0 ) {
			?>
			<table class="widefat gf_dashboard_view" cellspacing="0" style="border:0px;">
				<thead>
				<tr>
					<td class="gf_dashboard_form_title_header" style="text-align:left; padding:8px 18px!important; font-weight:bold;">
						<i><?php esc_html_e( 'Title', 'gravityforms' ); ?></i></td>
					<td class="gf_dashboard_entries_unread_header" style="text-align:center; padding:8px 18px!important; font-weight:bold;">
						<i><?php esc_html_e( 'Unread', 'gravityforms' ); ?></i></td>
					<td class="gf_dashboard_entries_total_header" style="text-align:center; padding:8px 18px!important; font-weight:bold;">
						<i><?php esc_html_e( 'Total', 'gravityforms' ); ?></i></td>
				</tr>
				</thead>

				<tbody class="list:user user-list">
				<?php
				foreach ( $forms as $form ) {
					if ( $form['is_trash'] ) {
						continue;
					}

					$date_display = GFCommon::format_date( $form['last_entry_date'] );
					if ( ! empty( $form['total_entries'] ) ) {

						?>
						<tr class='author-self status-inherit' valign="top">
							<td class="gf_dashboard_form_title column-title" style="padding:8px 18px;">
								<a <?php echo $form['unread_count'] > 0 ? "class='form_title_unread' style='font-weight:bold;'" : ''; ?> href="admin.php?page=gf_entries&view=entries&id=<?php echo absint( $form['id'] ); ?>"><?php echo esc_html( $form['title'] ); ?></a>
							</td>
							<td class="gf_dashboard_entries_unread column-date" style="padding:8px 18px; text-align:center;">
								<?php /* translators: 1. A timestamp like August 11, 2023 at 7:40 pm. */ ?>
								<a <?php echo $form['unread_count'] > 0 ? "class='form_entries_unread' style='font-weight:bold;'" : ''; ?> href="admin.php?page=gf_entries&view=entries&filter=unread&id=<?php echo absint( $form['id'] ); ?>" aria-label="<?php printf( esc_attr__( 'Last Entry: %s', 'gravityforms' ), esc_attr( $date_display ) ); ?>"><?php echo esc_html( absint( $form['unread_count'] ) ); ?></a>
							</td>
							<td class="gf_dashboard_entries_total column-date" style="padding:8px 18px; text-align:center;">
								<a href="admin.php?page=gf_entries&view=entries&id=<?php echo absint( $form['id'] ); ?>" aria-label="<?php esc_attr_e( 'View All Entries', 'gravityforms' ); ?>"><?php echo absint( $form['total_entries'] ); ?></a>
							</td>
						</tr>
						<?php
					}
				}
				?>
				</tbody>
			</table>

			<?php if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) : ?>
				<p class="textright">
				<i title="<?php esc_attr_e( 'Power Boost for Gravity Forms', 'power-boost-for-gravity-forms' ); ?>"><?php echo esc_html( self::get_cache_note() ); ?></i><a class="gf_dashboard_button button" href="admin.php?page=gf_edit_forms"><?php esc_html_e( 'View All Forms', 'gravityforms' ); ?></a>
			<?php endif; ?>
			</p>
			<?php
		} else {
			?>
			<div class="gf_dashboard_noforms_notice">
				<?php
				/* translators: 1. Anchor element opening tag. 2. Anchor element closing tag. */
				printf( esc_html__( 'You don\'t have any forms. Let\'s go %1$screate one %2$s!', 'gravityforms' ), '<a href="admin.php?page=gf_new_form">', '</a>' );
				?>
			</div>
			<?php
		}

		if ( GFCommon::current_user_can_any( 'gravityforms_view_updates' ) && ( ! function_exists( 'is_multisite' ) || ! is_multisite() || is_super_admin() ) ) {
			// displaying update message if there is an update and user has permission.
			GFForms::dashboard_update_message();
		}
	}

	/**
	 * Creates a short sentence telling the user their dashboard widget is
	 * cached.
	 *
	 * @return string
	 */
	protected static function get_cache_note() {
		$expires = (int) get_option( '_transient_timeout_gf_dashboard_unread_results', 0 );
		if ( empty( $expires ) ) {
			return 'Cached with no expiration.&nbsp; ';
		}
		$time_left = $expires - time();
		return 'Cached with max age ' . round( $time_left / MINUTE_IN_SECONDS ) . ' minutes.&nbsp; ';
	}

	/**
	 * Gets the form summary for all forms.
	 *
	 * @since  Unknown
	 * @access public
	 * @global $wpdb
	 *
	 * @uses GFFormsModel::get_form_table_name()
	 * @uses GFFormsModel::get_lead_table_name()
	 *
	 * @return array $forms Contains the form summary for all forms.
	 */
	public static function get_form_summary() {
		global $wpdb;
		$entry_table_name = version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ? esc_sql( GFFormsModel::get_lead_table_name() ) : esc_sql( GFFormsModel::get_entry_table_name() );
		$cache_duration   = apply_filters( 'gravityforms_dashboard_cache_duration', 6 * HOUR_IN_SECONDS );

		// Getting number of unread and total leads for all forms.
		$key            = 'gf_dashboard_unread_results';
		$unread_results = get_transient( $key );
		if ( empty( $unread_results ) ) {
			$unread_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT l.form_id, count(l.id) as unread_count
						FROM $entry_table_name l
						WHERE is_read=%d AND status=%s
						GROUP BY form_id",
					0,
					'active'
				),
				ARRAY_A
			);
			set_transient( $key, $unread_results, $cache_duration );
		}

		$key               = 'gf_dashboard_lead_date_results';
		$lead_date_results = get_transient( $key );
		if ( empty( $lead_date_results ) ) {
			$lead_date_results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT l.form_id, max(l.date_created) as last_entry_date, count(l.id) as total_entries
						FROM $entry_table_name l
						WHERE status=%s
						GROUP BY form_id",
					'active'
				),
				ARRAY_A
			);
			set_transient( $key, $lead_date_results, $cache_duration );
		}

		$key   = 'gf_dashboard_forms';
		$forms = get_transient( $key );
		if ( empty( $forms ) ) {
			$form_table_name = esc_sql( GFFormsModel::get_form_table_name() );
			$forms           = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, title, is_trash, '' as last_entry_date, 0 as unread_count
						FROM $form_table_name
						WHERE is_active=%d
						ORDER BY title",
					1
				),
				ARRAY_A
			);
			set_transient( $key, $forms, $cache_duration );
		}

		$count = count( $forms );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( is_array( $unread_results ) ) {
				foreach ( $unread_results as $unread_result ) {
					if ( $unread_result['form_id'] == $forms[ $i ]['id'] ) {
						$forms[ $i ]['unread_count'] = $unread_result['unread_count'];
						break;
					}
				}
			}

			if ( is_array( $lead_date_results ) ) {
				foreach ( $lead_date_results as $entry_date_result ) {
					if ( $entry_date_result['form_id'] == $forms[ $i ]['id'] ) {
						$forms[ $i ]['last_entry_date'] = $entry_date_result['last_entry_date'];
						$forms[ $i ]['total_entries']   = $entry_date_result['total_entries'];
						break;
					}
				}
			}
		}

		/**
		 * Modifies the summary of all forms, includes unread and total entry counts.
		 *
		 * @since 2.4.16
		 *
		 * @param array $forms Form summary.
		 */
		$forms = apply_filters( 'gform_form_summary', $forms );

		return $forms;
	}
}
