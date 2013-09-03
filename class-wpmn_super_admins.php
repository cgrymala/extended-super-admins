<?php
/**
 * Classes and methods for extended super admins in a multi-network setup
 * @package WordPress
 * @subpackage ExtendedSuperAdmins
 * @since 0.1a
 * @version 0.7
 */

if ( ! class_exists( 'extended_super_admins' ) )
	require_once( 'class-extended_super_admins.php' );

if ( class_exists( 'extended_super_admins' ) && ! class_exists( 'wpmn_super_admins' ) ) {
	/**
	 * The class for extended super admins in a WordPress Multi-Network setup
	 * @package WordPress
	 * @subpackage ExtendedSuperAdmins
	 */
	class wpmn_super_admins extends extended_super_admins {
		
		var $multi_network_admins = array();
		
		function __construct() {
			if ( ! parent::__construct() )
				return false;
			
			$this->is_multi_network = true;
			$this->can_manage_plugin();
			
			add_action( 'plugins_loaded', 'set_multi_network_admins' );
			add_filter( 'network_admin_plugin_action_links_' . ESA_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
		}
		
		function add_settings_link( $links ) {
			if ( ! $this->can_manage_plugin() )
				return array( 'multinetwork_activate' => null, 'multinetwork_deactivate' => null );
			
			$links = parent::add_settings_link( $links );
			
			global $wp_version;
			$options_page = ( version_compare( $wp_version, '3.0.9', '>' ) ) ? 'settings' : 'ms-admin';
			if ( ! strstr( __FILE__, 'mu-plugins' ) ) {
				$links['multinetwork_activate'] = '<br/><a href="' .
					wp_nonce_url( $options_page .
					'.php?options-action=multi_network_activate&page=' .
					ESA_OPTIONS_PAGE, '_esa_multi_network' ) .
					'">' .
					__( 'Multi-Network Activate', ESA_TEXT_DOMAIN ) .
					'</a>';
				$links['multinetwork_deactivate'] = '<a href="' .
					wp_nonce_url( $options_page .
					'.php?options-action=multi_network_deactivate&page=' .
					ESA_OPTIONS_PAGE, '_esa_multi_network' ) .
					'">' .
					__( 'Deactivate On All Networks', ESA_TEXT_DOMAIN ) .
					'</a>';
			}
			
			return $links;
		}
		
		function can_manage_plugin() {
			global $current_user;
			get_currentuserinfo();
			
			if ( $this->perms_checked ) {
				if ( $this->_use_log )
					error_log( '[ESA Notice]: The current list of this user\'s caps looks like: ' . print_r( $current_user->allcaps, true ) );
				if ( $this->_use_log )
					error_log( '[ESA Notice]: The permissions have already been checked. It was determined that the current user is ' . ( $this->current_user_can( 'manage_esa_options' ) ? '' : 'not ' ) . 'able to manage this plugin.' );
				
				return $this->current_user_can( 'manage_esa_options' );
			}
			
			global $current_user;
			get_currentuserinfo();
			/*$user = new WP_User( $current_user->ID );*/
			
			if ( is_admin() && ! is_network_admin() ) {
				$current_user->remove_cap( 'manage_esa_options' );
				if ( empty( $current_user->caps ) )
					/*wp_die( var_dump( $current_user ) );*/
					remove_user_from_blog( $current_user->ID );
				return false;
			}
			
			if ( $this->is_full_network_admin() ) {
				if ( $this->_use_log )
					error_log( '[ESA Notice]: The current user was determined to be a full network admin, and is therefore able to manage the settings for this plugin.' );
				$current_user->add_cap( 'manage_esa_options' );
			} else {
				if ( $this->_use_log )
					error_log( '[ESA Notice]: The current user is not a multi-network admin, so no ESA permissions were granted.' );
				$current_user->remove_cap( 'manage_esa_options' );
			}
				
			return $this->current_user_can( 'manage_esa_options' );
		}
		
		/**
		 * Get a list of users that are Super Admins on all networks in the installation
		 *
		 * Retrieves the lists of Super Admins for each individual network, then checks 
		 * for the users that are on all of those lists. Returns the array of users that
		 * are Super Admins for all of the networks.
		 * @return array list of usres that are super admins for entire set of networks
		 */
		function get_super_network_admins() {
			global $wpdb;
	
			$full_network_admins = array();
			
			/* Get a list of all networks */
			$networks = $wpdb->get_results( "SELECT DISTINCT id FROM " . $wpdb->site );
			$network_admins = array();
			$all_super_admins = array();
			$network_admins = $wpdb->get_results( $wpdb->prepare( "SELECT site_id, meta_value FROM {$wpdb->sitemeta} WHERE meta_key = %s", 'site_admins' ) );
			foreach ( $network_admins as $network ) {
				$all_super_admins = array_merge( $all_super_admins, maybe_unserialize( $network->meta_value ) );
				$full_network_admins = array_intersect( $all_super_admins, maybe_unserialize( $network->meta_value ) );
			}
//			$i = 0;
//			/* Get lists of all super admins for each network */
//			if( count( $networks ) ) {
//				foreach( $networks as $network ) {
//					/*$this->switch_to_site( $network->id );*/
//					$network_admins[$i] = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM " . $wpdb->sitemeta . " WHERE site_id = " . $network->id . " AND meta_key = 'site_admins'" ) ) );
//					$all_super_admins = array_merge( $all_super_admins, $network_admins[$i] );
//					/*$this->restore_current_site();*/
//					$i++;
//				}
//			}
//			$all_super_admins = array_unique( $all_super_admins );
//			/* Find out which Super Admins are admins on all networks */
//			foreach( $network_admins as $k=>$v ) {
//				$full_network_admins = array_intersect( $all_super_admins, $v );
//				/* If, at any point, our array of full network admins is empty, we return an empty array; since a user obviously can't be a super admin on *all* networks if s/he isn't an admin on the one we just checked */
//				if( !count($full_network_admins) )
//					return array();
//			}
			
			return $full_network_admins;
		}
		
		/**
		 * Check to see if the current user is a full network admin
		 * @return bool whether or not the user is a full network admin
		 */
		function is_full_network_admin() {
			if ( empty( $this->multi_network_admins ) )
				$this->set_multi_network_admins();
			
			global $current_user;
			get_currentuserinfo();
			return in_array( $current_user->user_login, $this->multi_network_admins );
		}
		
		function set_multi_network_admins() {
			if ( ! is_super_admin() )
				return;
				
			$this->multi_network_admins = $this->get_super_network_admins();
		}
		
		function get_super_admin_list() {
			if ( ! empty( $this->all_super_admins ) )
				return $this->all_super_admins;
			
			global $wpdb;
			$all_super_admins = array();
			$super_admins = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->sitemeta} WHERE meta_key = %s", 'site_admins' ) );
			foreach ( $super_admins as $site_admins ) {
				$site_admins = maybe_unserialize( $site_admins->meta_value );
				$all_super_admins = array_merge( $all_super_admins, $site_admins );
			}
			$this->all_super_admins = array_unique( $all_super_admins );
			return $this->all_super_admins;
		}
		
		function save_options( $values_to_use=NULL ) {
			if ( empty( $values_to_use ) ) {
				$this->debug = '<div class="error">';
				$this->debug .= '<p>' . __( 'The form to save the Extended Super Admin options was submitted, but the values were empty. Therefore, nothing was updated.', ESA_TEXT_DOMAIN ) . '</p>';
				$this->debug .= '</div>';
				return;
			}
			
			/*$this->set_options( $values_to_use );
			$this->options = $this->get_options();*/
			
			global $wpdb;
			$networks = $wpdb->get_results( "SELECT DISTINCT id FROM " . $wpdb->site );
			$updated = true;
			foreach ( $networks as $network ) {
				$this->switch_to_site( $network->id );
				global $new_whitelist_options;
				if ( ! is_array( $new_whitelist_options ) || ! array_key_exists( ESA_OPTION_NAME, $new_whitelist_options ) )
					register_setting( ESA_OPTION_NAME, ESA_OPTION_NAME, array( $this, 'verify_options' ) );
				$upd = update_site_option( ESA_OPTION_NAME, $values_to_use );
				if( !$upd )
					$updated = false;
				$this->restore_current_site();
			}
			
			if ( $updated ) {
				$this->debug = '<div class="updated">';
				$this->debug .= '<p>' . __( 'The options for the Extended Super Admins plugin have been updated.', ESA_TEXT_DOMAIN ) . '</p>';
				
				/*ob_start();
				var_dump( $this->options );
				$this->debug .= '<div><pre><code>' . ob_get_contents() . '</code></pre></div>';
				ob_end_clean();*/
				
				$this->debug .= '</div>';
			} else {
				$this->debug .= '<div class="error">';
				$this->debug .= '<p>' . __( 'There was an error committing the changes to the database.', ESA_TEXT_DOMAIN ) . '</p>';
				
				/*ob_start();
				var_dump( $this->options );
				$this->debug .= '<div><pre><code>' . ob_get_contents() . '</code></pre></div>';
				ob_end_clean();*/
				
				$this->debug .=  '</div>';
			}
			unset( $this->options );
			$this->set_options();
			return $this->options;
		}
		
		/**
		 * Remove this plugin's settings from the database
		 */
		function delete_settings() {
			global $wpdb;
			$networks = $wpdb->get_results( "SELECT DISTINCT id FROM " . $wpdb->site );
			$updated = true;
			foreach ( $networks as $network ) {
				$this->switch_to_site( $network->id );
				delete_site_option( ESA_OPTION_NAME );
				$this->restore_current_site();
			}
			return print('<div class="warning">The settings for this plugin have been deleted.</div>');
		}
		
		/**
		 * Determine how to best switch networks
		 * @param int $site_id the ID of the network to which to switch
		 * @uses wpmn_super_admins::switch_to_network()
		 * @since 0.4a
		 */
		function switch_to_site( $site_id ) {
			/*if( function_exists( 'wpmn_switch_to_network' ) ) {
				return wpmn_switch_to_network( $site_id );
			} elseif( function_exists( 'switch_to_site' ) ) {
				if( empty( $GLOBALS['sites'] ) ) {
					global $wpdb;
					$GLOBALS['sites'] = $wpdb->get_results('SELECT * FROM ' . $wpdb->site);
				}
				return switch_to_site( $site_id );
			}*/
			
			$this->switch_to_network( $site_id );
			
			return true;
		}
		/**
		 * Perform the actual network switch
		 * @param int $new_site_id the ID of the network to which to switch
		 * @uses $GLOBALS['wpdb']
		 * @uses $GLOBALS['previous_site']
		 * @uses $GLOBALS['current_site']
		 * @uses get_current_site()
		 * @since 0.7a
		 */
		function switch_to_network( $new_site_id ) {
			global $wpdb;
			$site_info = $wpdb->get_results( "SELECT site_id, blog_id FROM {$wpdb->blogs} GROUP BY site_id" );
			if ( empty( $site_info ) )
				return false;
			
			foreach ( $site_info as $s ) {
				if ( $new_site_id == $s->site_id )
					$new_blog_id = $s->blog_id;
			}
			if ( empty( $new_blog_id ) )
				return false;
			
			$GLOBALS['previous_site']->site_id = $GLOBALS['site_id'];
			$GLOBALS['previous_site']->blog_id = $wpdb->set_blog_id( $new_blog_id, $new_site_id );
			$GLOBALS['current_site'] = get_current_site();
			return true;
		}
		
		/**
		 * Determine how to best switch back to original network
		 * @uses wpmn_super_admins::restore_current_network()
		 * @since 0.4a
		 */
		function restore_current_site() {
			/*if( function_exists( 'wpmn_restore_current_network' ) )
				return wpmn_restore_current_network();
			elseif( function_exists( 'restore_current_site' ) )
				return restore_current_site();*/
			
			$this->restore_current_network();
			return true;
		}
		/**
		 * Perform the actual network switch
		 * @uses $GLOBALS['wpdb']
		 * @uses $GLOBALS['previous_site']
		 * @uses $GLOBALS['current_site']
		 * @uses get_current_site()
		 * @since 0.7a
		 */
		function restore_current_network() {
			if ( ! isset( $GLOBALS['previous_site'] ) || empty( $GLOBALS['previous_site'] ) )
				return false;
			
			global $wpdb;
			$wpdb->set_blog_id( $GLOBALS['previous_site']->blog_id, $GLOBALS['previous_site']->site_id );
			$GLOBALS['current_site'] = get_current_site();
		}
		
	}
}
?>