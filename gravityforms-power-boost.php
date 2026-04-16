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
 * Version: 3.2.6
 * Text Domain: power-boost-for-gravity-forms
 * License: GPLv2 or later
 * GitHub Plugin URI: csalzano/power-boost-for-gravity-forms
 * Primary Branch: main
 */

if ( ! defined( 'GF_POWER_BOOST_PLUGIN_ROOT' ) ) {
	define( 'GF_POWER_BOOST_PLUGIN_ROOT', __FILE__ );
}

require_once 'includes/class-gravityforms-power-boost.php';
$gfpb_power_boost = new GravityForms_Power_Boost();
$gfpb_power_boost->add_hooks();
