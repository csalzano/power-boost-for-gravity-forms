<?php
defined( 'ABSPATH' ) or exit;

/**
 * Plugin Name: Gravity Forms Power Boost
 * Description: Enhances the dashboard for Gravity Forms power users. Adds "Last Entry" to the forms list.
 * Author: Corey Salzano
 * Version: 1.0.0
 * License: GPLv2 or later
 */

class Gravity_Forms_Power_Boost
{
	public function add_columns_to_list_table( $columns )
	{
		$columns['last_entry'] = esc_html__( 'Last Entry', 'gravityforms' );
		return $columns;
	}

	public function add_hooks()
	{
		//Add columns to the table that lists Forms on edit.php
		add_filter( 'gform_form_list_columns', array( $this, 'add_columns_to_list_table' ) );

		//Populate the columns we added to the list table
		add_action( 'gform_form_list_column_last_entry', array( $this, 'populate_columns_we_added' ), 10, 1 );
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
