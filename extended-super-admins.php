<?php
/*
Plugin Name: Extended Super Admins
Description: Allows you to control the capabilities of specific Super Admins in multisite
Version:     1.0
Author:      Curtiss Grymala
Author URI:  http://ten-321.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: extended-super-admin
Network:     true
*/

if ( ! class_exists( 'Extended_Super_Admins' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . '/classes/class-extended-super-admins.php' );
	function inst_extended_super_admin_obj() {
		global $extended_super_admin_obj;
		$extended_super_admin_obj = new Extended_Super_Admin;
	}
	add_action( 'plugins_loaded', 'inst_extended_super_admin_obj' );
}