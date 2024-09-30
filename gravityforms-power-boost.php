<?php
/**
 * Power Boost for Gravity Forms
 *
 * @package Gravity_Forms_Power_Boost
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Name: Power Boost for Gravity Forms
 * Plugin URI: https://breakfastco.xyz/power-boost-for-gravity-forms/
 * Description: Enhances the dashboard for Gravity Forms power users.
 * Author: Breakfast
 * Author URI: https://breakfastco.xyz
 * Version: 3.1.7
 * Text Domain: power-boost-for-gravity-forms
 * License: GPLv2 or later
 */

if ( ! defined( 'GF_POWER_BOOST_PLUGIN_ROOT' ) ) {
	define( 'GF_POWER_BOOST_PLUGIN_ROOT', __FILE__ );
}

require_once 'includes/class-gravityforms-power-boost.php';
$power_boost_9000 = new GravityForms_Power_Boost();
$power_boost_9000->add_hooks();
