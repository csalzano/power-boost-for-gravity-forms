<?php
defined( 'ABSPATH' ) or exit;

/**
 * Plugin Name: Gravity Forms Power Boost
 * Description: Enhances the dashboard for Gravity Forms power users.
 * Author: Corey Salzano
 * Version: 1.1.0
 * License: GPLv2 or later
 */

class Gravity_Forms_Power_Boost
{
	var $rendered_form_ids;

	public function add_columns_to_list_table( $columns )
	{
		$columns['last_entry'] = esc_html__( 'Last Entry', 'gravityforms' );
		return $columns;
	}

	public function add_field_ids_when_viewing_entries( $content, $field, $value, $entry_id, $form_id )
	{
		return preg_replace( '/(class="entry\-view\-field\-name">)([^<]+)(<\/td>)/', '$1 ' . $field['id'] . '. $2$3', $content );
	}

	public function add_hooks()
	{
		//Add columns to the table that lists Forms on edit.php
		add_filter( 'gform_form_list_columns', array( $this, 'add_columns_to_list_table' ) );

		//Populate the columns we added to the list table
		add_action( 'gform_form_list_column_last_entry', array( $this, 'populate_columns_we_added' ), 10, 1 );

		//Keep track of all Gravity Forms form IDs that are rendered during this request
		add_filter( 'gform_pre_render', array( $this, 'save_rendered_form_ids' ), 10, 3 );
		
		//Change the Forms menu of the admin bar
		add_action( 'wp_before_admin_bar_render', array( $this, 'enhance_admin_bar' ), 99 );

		//Include a style sheet to customize the admin bar
		add_action( 'wp_enqueue_scripts', array( $this, 'include_admin_bar_css' ) );

		//When viewing entries, put field IDs near labels
		add_filter( 'gform_field_content', array( $this, 'add_field_ids_when_viewing_entries' ), 10, 5 );
	}

	public function include_admin_bar_css()
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

	public function enhance_admin_bar()
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
							'title'  => esc_html__( 'Edit', 'gravityforms' ),
							'href'   => admin_url( 'admin.php?page=gf_edit_forms&id=' . $form_id ),
						)
					);
				}

				if ( GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
					$wp_admin_bar->add_node(
						array(
							'id'     => 'gform-form-' . $form_id . '-entries',
							'parent' => 'gform-form-' . $form_id,
							'title'  => esc_html__( 'Entries', 'gravityforms' ),
							'href'   => admin_url( 'admin.php?page=gf_entries&id=' . $form_id ),
						)
					);
				}

				if ( GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
					$wp_admin_bar->add_node(
						array(
							'id'     => 'gform-form-' . $form_id . '-settings',
							'parent' => 'gform-form-' . $form_id,
							'title'  => esc_html__( 'Settings', 'gravityforms' ),
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
							'title'  => esc_html__( 'Preview', 'gravityforms' ),
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

}
$power_boost_9000 = new Gravity_Forms_Power_Boost();
$power_boost_9000->add_hooks();
