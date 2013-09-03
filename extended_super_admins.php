<?php
/*
Plugin Name: Extended Super Admins
Version: 0.7b
Plugin URI: http://plugins.ten-321.com/extended-super-admins/
Description: Enables the creation of multiple levels of "Super Admins" within WordPress Multi Site. Multiple new roles can be created, and all capabilities generally granted to a "Super Admin" can be revoked individually.
Author: Curtiss Grymala
Author URI: http://ten-321.com/
Network: true
License: GPL2
Text Domain: esa_text_domain

---

Copyright 2011 Curtiss Grymala (email: cgrymala@umw.edu)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

function instantiate_extended_super_admins() {
	if ( ! is_multisite() )
		return;
	if ( is_multinetwork() ) {
		require_once( 'class-wpmn_super_admins.php' );
		$wpsa = new wpmn_super_admins;
		$wpsa->is_multi_network = true;
		return $wpsa;
	} else {
		require_once( 'class-extended_super_admins.php' );
		$wpsa = new extended_super_admins;
		$wpsa->is_multi_network = false;
		return $wpsa;
	}
}
if ( ! function_exists( 'is_multinetwork' ) ) {
	function is_multinetwork() {
		if ( function_exists( 'wpmn_switch_to_network' ) || function_exists( 'switch_to_site' ) || function_exists( 'ra_network_page' ) )
			return true;
			
		if ( ! file_exists( WP_PLUGIN_DIR . '/wordpress-multi-network/wordpress-multi-network.php' ) && ! file_exists( WPMU_PLUGIN_DIR . '/wordpress-multi-network.php' ) && ! file_exists( WP_PLUGIN_DIR . '/networks-for-wordpress/index.php' ) && ! file_exists( WPMU_PLUGIN_DIR . '/networks-for-wordpress.php' ) && ! file_exists( WP_PLUGIN_DIR . '/Networks-Plus/ra-networks.php' ) && ! file_exists( WPMU_PLUGIN_DIR . '/ra-network.php' ) )
			return false;
		
		global $wpdb;
		$plugins = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->sitemeta} WHERE meta_key = %s", 'active_sitewide_plugins' ) );
		foreach ( $plugins as $plugin ) {
			if ( array_key_exists( 'wordpress-multi-network/wordpress-multi-network.php', maybe_unserialize( $plugin->meta_value ) ) ) {
				require_once( WP_PLUGIN_DIR . '/wordpress-multi-network/wordpress-multi-network.php' );
				return true;
			} elseif ( array_key_exists( 'networks-for-wordpress/index.php', maybe_unserialize( $plugin->meta_value ) ) ) {
				require_once( WP_PLUGIN_DIR . '/networks-for-wordpress/index.php' );
				return true;
			} elseif ( array_key_exists( 'Networks-Plus/ra-networks.php', maybe_unserialize( $plugin->meta_value ) ) ) {
				require_once( WP_PLUGIN_DIR . '/Networks-Plus/ra-networks.php' );
				return true;
			}
		}
		$sites = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}" );
		foreach ( $sites as $site ) {
			$oldblog = $wpdb->set_blog_id( $site->blog_id );
			$plugins = $wpdb->get_results( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'active_plugins' ) );
			foreach ( $plugins as $plugin ) {
				if ( array_key_exists( 'wordpress-multi-network/wordpress-multi-network.php', maybe_unserialize( $plugin->option_value ) ) ) {
					require_once( WP_PLUGIN_DIR . '/wordpress-multi-network/wordpress-multi-network.php' );
					return true;
				} elseif ( array_key_exists( 'networks-for-wordpress/index.php', maybe_unserialize( $plugin->option_value ) ) ) {
					require_once( WP_PLUGIN_DIR . '/networks-for-wordpress/index.php' );
					return true;
				} elseif ( array_key_exists( 'Networks-Plus/ra-networks.php', maybe_unserialize( $plugin->option_value ) ) ) {
					require_once( WP_PLUGIN_DIR . '/Networks-Plus/ra-networks.php' );
					return true;
				}
			}
			$wpdb->set_blog_id( $oldblog );
		}
		
		return false;
	}
}
add_action( 'plugins_loaded', 'instantiate_extended_super_admins' );
?>