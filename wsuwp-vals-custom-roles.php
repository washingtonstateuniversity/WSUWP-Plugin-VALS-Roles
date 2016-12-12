<?php
/*
Plugin Name: WSUWP VALS Custom Roles
Version: 0.0.1
Description: Adds custom Test Center Admins and Registered Trainees roles.
Author: washingtonstateuniversity
Author URI: https://web.wsu.edu/
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Plugin-VALS-Roles
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// The core plugin class.
require dirname( __FILE__ ) . '/includes/class-wsuwp-vals-custom-roles.php';

add_action( 'after_setup_theme', 'WSUWP_VALS_Custom_Roles' );
/**
 * Start things up.
 *
 * @return \WSUWP_VALS_Custom_Roles
 */
function WSUWP_VALS_Custom_Roles() {
	return WSUWP_VALS_Custom_Roles::get_instance();
}
