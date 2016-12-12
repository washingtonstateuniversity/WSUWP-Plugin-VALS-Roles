<?php
class WSUWP_VALS_Custom_Roles {
	/**
	 * @var WSUWP_VALS_Custom_Roles
	 */
	private static $instance;

	/**
	 * Maintain and return the one instance.
	 * Initiate hooks when called the first time.
	 *
	 * @since 0.0.1
	 *
	 * @return \WSUWP_VALS_Custom_Roles
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWP_VALS_Custom_Roles();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks to include.
	 *
	 * @since 0.0.1
	 */
	public function setup_hooks() {}
}
