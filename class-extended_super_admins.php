<?php
/**
 * Includes the constants and defines the class for setting up multiple levels of super admins
 * @package WordPress
 * @subpackage ExtendedSuperAdmins
 * @since 0.1a
 * @version 0.7
 */

/**
 * Require the file that sets the constants for this plugin
 * @package WordPress
 * @subpackage ExtendedSuperAdmins
 */
require_once( str_replace( 'class-', 'constants-', __FILE__ ) );

if( !class_exists( 'extended_super_admins' ) ) {
	/**
	 * A class to handle multiple levels of "Super Admins" in WordPress
	 * @package WordPress
	 * @subpackage ExtendedSuperAdmins
	 */
	class extended_super_admins {
		/**
		 * An array of identifiers for the different roles
		 * @var array
		 */
		var $role_id = array();
		/**
		 * An array of the friendly names for these roles
		 * @var array
		 */
		var $role_name = array();
		/**
		 * An array of the capabilities to be removed from each role
		 * @var array
		 */
		var $role_caps = array();
		/**
		 * An array of the user logins that belong to each role
		 * @var array
		 */
		var $role_members = array();
		/**
		 * An internal array of the options as sent to/retrieved from the database
		 * @var array
		 */
		var $options = array();
		/**
		 * An internal array of the capabilities available
		 * @var array
		 */
		var $allcaps = array();
		/**
		 * A variable to determine whether we've checked this user's permissions yet
		 * @var bool
		 */
		var $perms_checked = false;
		/**
		 * An array to hold the Codex descriptions of each capability
		 * @var array
		 * @since 0.4a
		 */
		var $caps_descriptions = array(
			'manage_esa_options' => '<div id="_role_caps_manage_esa_options" class="_role_caps"><h3>manage_esa_options</h3><div class="_single_cap"><p>Capability specific to the Extended Super Admins plugin. Allows user to manage the options for the Extended Super Admins plugin.</p></div></div>',
		);
		/**
		 * Determines whether to send error messages to the log
		 */
		protected $_use_log = false;
		
		/**
		 * Create our extended_super_admins object
		 */
		function __construct() {
			if( !is_multisite() || !is_user_logged_in() )
				return false;
			
			if( defined( 'WP_DEBUG' ) && WP_DEBUG )
				$this->_use_log = true;
			
			if( $this->_use_log )
				error_log( '[ESA Notice]: Constructing the ESA object' );
			
			$esa_options = isset( $GLOBALS['esa_options'] ) ? $GLOBALS['esa_options'] : NULL;
			$force_update = isset( $GLOBALS['force_esa_options_update'] ) ? $GLOBALS['force_esa_options_update'] : false;
			$this->set_options( $esa_options, $force_update );
			$r = get_site_option( '_esa_deleted_deprecated_role', false );
			if ( false === $r ) {
				$r = get_role( 'esa_plugin_manager' );
				if ( ! empty( $r ) && ! is_wp_error( $r ) )
					remove_role( 'esa_plugin_manager' );
				update_site_option( '_esa_deleted_deprecated_role', true );
			}
			
			$this->can_manage_plugin();
			
			add_filter( 'map_meta_cap', array( $this, 'revoke_privileges' ), 0, 4 );
			add_action( 'network_admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'init', array( $this, '_init' ) );
			add_action( 'admin_init', array( $this, '_admin_init' ) );
			add_filter('plugin_action_links_' . ESA_PLUGIN_BASENAME, array($this, 'add_settings_link'));
			add_filter('network_admin_plugin_action_links_' . ESA_PLUGIN_BASENAME, array($this, 'add_settings_link'));
			
			return true;
		}
		
		function _init() {
			if( function_exists( 'load_plugin_textdomain' ) )
				load_plugin_textdomain( ESA_TEXT_DOMAIN, false, ESA_PLUGIN_PATH . '/lang/' );
				
			if( is_admin() && isset( $_REQUEST['page'] ) && ESA_OPTIONS_PAGE == $_REQUEST['page'] ) {
				/* Clean out the old method of storing Codex information */
				if( false !== get_site_option( '_esa_capsCodexInfo' ) )
					delete_site_option( '_esa_capsCodexInfo' );
				
				if( function_exists( 'wp_register_style' ) ) {
					wp_register_style( 'esa_admin_styles', plugins_url( 'css/extended_super_admins.min.css', __FILE__ ), array(), '0.7', 'all' );
				}
				if( function_exists( 'wp_register_script' ) ) {
					wp_register_script( 'esa_admin_scripts', plugins_url( 'scripts/extended_super_admins.min.js', __FILE__ ), array('jquery','post'), '0.7', true );
				}
				
				if( version_compare( '3.0.9', $GLOBALS['wp_version'], '<' ) ) {
					add_action( 'load-settings_page_' . ESA_OPTIONS_PAGE, array( $this, 'add_settings_meta_boxes' ) );
					add_action( 'load-settings_page_' . ESA_OPTIONS_PAGE, array( $this, '_save_settings_options' ) );
				} else {
					add_action( '_admin_menu', array( $this, 'add_settings_meta_boxes' ) );
					add_action( '_admin_menu', array( $this, '_save_settings_options' ) );
				}
			}
				
			return true;
		}
		
		function _admin_init() {
			if( isset( $_REQUEST['page'] ) && ESA_OPTIONS_PAGE == $_REQUEST['page'] ) {
				if( function_exists( 'wp_enqueue_script' ) ) {
					wp_enqueue_script( 'esa_admin_scripts' );
				}
				
				if( function_exists( 'wp_enqueue_style' ) ) {
					wp_enqueue_style( 'esa_admin_styles' );
				}
				
				if( function_exists( 'register_setting' ) )
					register_setting( ESA_OPTION_NAME, ESA_OPTION_NAME, array( $this, 'verify_options' ) );
				else
					/*wp_die( 'The <code>register_setting()</code> function is not available.' );*/
					add_filter( 'sanitize_option_' . ESA_OPTION_NAME, array( $this, 'verify_options' ) );
			}
		}
		
		function add_settings_link( $links ) {
			global $wp_version;
			if( !$this->can_manage_plugin() )
				return array();
			
			$options_page = ( version_compare( $wp_version, '3.0.9', '>' ) ) ? 'settings' : 'ms-admin';
			$slink = array( 'settings_link' => '<a href="' . $options_page . '.php?page=' . ESA_OPTIONS_PAGE . '">' . __( 'Settings', ESA_TEXT_DOMAIN ) . '</a>' );
			$links = array_merge( $slink, $links );
			
			$slink = array( 'delete_settings_link' => '<a href="' . $options_page . '.php?page=' . ESA_OPTIONS_PAGE . '&options-action=remove_settings">' . __( 'Delete Settings', ESA_TEXT_DOMAIN ) . '</a>' );
			$links = array_merge( $links, $slink );
			
			return $links;
		}
		
		/**
		 * Determine whether or not this user is allowed to modify this plugin's options
		 */
		function can_manage_plugin() {
			if( $this->perms_checked ) {
				if( $this->_use_log )
					error_log( '[ESA Notice]: The permissions have already been checked. It was determined that the current user is ' . ( $this->current_user_can( 'manage_esa_options' ) ? '' : 'not ' ) . ' able to manage this plugin.' );
				return $this->current_user_can( 'manage_esa_options' );
			}
			
			global $current_user;
			get_currentuserinfo();
			
			if( is_admin() && !is_network_admin() ) {
				$current_user->remove_cap( 'manage_esa_options' );
				if( empty( $current_user->caps ) )
					/*wp_die( var_dump( $current_user ) );*/
					remove_user_from_blog( $current_user->ID );
				return false;
			}
			
			if( is_super_admin() && current_user_can( 'manage_network_plugins' ) && current_user_can( 'manage_network_users' ) ) {
				if( $this->_use_log )
					error_log( '[ESA Notice]: The user with a login of ' . $current_user->user_login . ' should be able to manage this plugin' );
				$current_user->add_cap( 'manage_esa_options' );
				return true;
			} else {
				if( $this->_use_log )
					error_log( '[ESA Notice]: The user with a login of ' . $current_user->user_login . ' is not able to manage this plugin' );
				$current_user->remove_cap( 'manage_esa_options' );
				return false;
			}
		}
		
		/**
		 * Override the normal current_user_can() function, since it seems to choke on either network caps or custom caps
		 */
		function current_user_can( $cap ) {
			global $current_user;
			get_currentuserinfo();
			return array_key_exists( $cap, $current_user->caps ) && $current_user->caps[$cap];
		}
		
		/**
		 * Set the object's parameters
		 * @param array the values to use to set the options
		 */
		function set_options( $values_to_use=NULL, $force_update=false ) {
			$options_set = false;
			if( !empty( $values_to_use ) ) {
				$options_set = true;
				
				$this->options = array(
					'role_id'		=> $values_to_use['role_id'],
					'role_name'		=> $values_to_use['role_name'],
					'role_members'	=> $values_to_use['role_members'],
					'role_caps'		=> $values_to_use['role_caps'],
				);
				foreach( $this->options['role_id'] as $id ) {
					if( empty( $this->options['role_name'][$id] ) ) {
						unset( $this->options['role_id'][$id], $this->options['role_name'][$id], $this->options['role_members'][$id], $this->options['role_caps'][$id] );
						$this->debug .= '<div class="error">' . __( 'One of the roles you attempted to create did not have a name. Therefore it was not saved. Please try again.', ESA_TEXT_DOMAIN ) . '</div>';
					} else {
						if( empty( $this->options['role_members'][$id] ) ) {
							$this->options['role_members'][$id] = array(0=>NULL);
						}
						if( empty( $this->options['role_caps'][$id] ) ) {
							$this->options['role_caps'][$id] = array(0=>NULL);
						}
					}
					
					if( ( empty( $this->options['role_name'][$id] ) && empty( $this->options['role_members'][$id] ) && empty( $this->options['role_caps'][$id] ) ) || ( isset($values_to_use['delete_role'][$id] ) && 'on' == $values_to_use['delete_role'][$id] ) ) {
						unset( $this->options['role_id'][$id], $this->options['role_name'][$id], $this->options['role_members'][$id], $this->options['role_caps'][$id] );
					}
				}
				if( empty( $this->options ) || false !== $force_update ) {
					delete_site_option( ESA_OPTION_NAME );
					add_site_option( ESA_OPTION_NAME, ( empty( $this->options ) ? array() : $this->options ) );
				}
				
				/*ob_start();
				var_dump( $this->options );
				$var_output = ob_get_contents();
				ob_end_clean();
				
				print( '<p>' . __( sprintf( 'The current site is set to %d and the options look like:', $GLOBALS['site_id'] ), ESA_TEXT_DOMAIN ) . '</p><pre><code>' . $var_output . '</code></pre>' );*/
			}
			
			if( empty( $this->options ) ) {
				$this->options = get_site_option( ESA_OPTION_NAME, array(), false );
			}
			
			if( empty( $this->options ) ) {
				add_site_option( ESA_OPTION_NAME, array() );
				$this->options = array();
			}
			
			foreach( $this->options as $key=>$var ) {
				if( !is_array( $var ) ) {
					continue;
				}
				if( property_exists( $this, $key ) ) {
					$this->$key = $var;
				}
			}
			return;
		}
		
		/**
		 * Find out whether the WP Multi Network plugin is active
		 * Added support for ra-networks.php in v0.7a
		 */
		function is_multi_network() {
			if( isset( $this->is_multi_network ) )
				return $this->is_multi_network;
			
			if( function_exists( 'wpmn_switch_to_network' ) || function_exists( 'switch_to_site' ) || function_exists( 'ra_network_page' ) ) {
				$this->is_multi_network = true;
				return $this->is_multi_network;
			}
				
			if( !file_exists( WP_PLUGIN_DIR . '/wordpress-multi-network/wordpress-multi-network.php' ) && !file_exists( WPMU_PLUGIN_DIR . '/wordpress-multi-network.php' ) && !file_exists( WP_PLUGIN_DIR . '/networks-for-wordpress/index.php' ) && !file_exists( WPMU_PLUGIN_DIR . '/networks-for-wordpress.php' ) && !file_exists( WP_PLUGIN_DIR . '/Networks-Plus/ra-networks.php' ) && !file_exists( WPMU_PLUGIN_DIR . '/ra-network.php' ) ) {
				$this->is_multi_network = false;
				return $this->is_multi_network;
			}
			
			global $wpdb;
			$plugins = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->sitemeta} WHERE meta_key = %s", 'active_sitewide_plugins' ) );
			foreach( $plugins as $plugin ) {
				if( 
				   in_array( 'wordpress-multi-network/wordpress-multi-network.php', maybe_unserialize( $plugin->meta_value ) ) || 
				   in_array( 'networks-for-wordpress/index.php', maybe_unserialize( $plugin->meta_value ) ) || 
				   in_array( 'Networks-Plus/ra-networks.php', maybe_unserialize( $plugin->meta_value ) ) 
				) {
					$this->is_multi_network = true;
					return $this->is_multi_network;
				}
			}
			$sites = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}" );
			foreach( $sites as $site ) {
				$oldblog = $wpdb->set_blog_id( $site->blog_id );
				$plugins = $wpdb->get_results( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'active_plugins' ) );
				foreach( $plugins as $plugin ) {
					if( 
					   in_array( 'wordpress-multi-network/wordpress-multi-network.php', maybe_unserialize( $plugin->option_value ) ) || 
					   in_array( 'networks-for-wordpress/index.php', maybe_unserialize( $plugin->option_value ) ) || 
					   in_array( 'Networks-Plus/ra-networks.php', maybe_unserialize( $plugin->option_value ) ) 
					) {
						$this->is_multi_network = true;
						return $this->is_multi_network;
					}
				}
			}
			
			$this->is_multi_network = false;
			return $this->is_multi_network;
		}
		
		/**
		 * Return the array of the options
		 * @return array the array of options (suitable for saving to the database)
		 */
		function get_options() {
			$options = array();
			$keys = array( 'role_id', 'role_name', 'role_caps', 'role_members' );
			foreach( $keys as $key ) {
				$options[$key] = $this->$key;
			}
			
			return $options;
		}
		
		function admin_notice() {
			if( empty( $this->debug ) )
				return;
			
			echo $this->debug;
			unset( $this->debug );
		}
		
		/**
		 * Save the options to the database 
		 */
		function save_options( $values_to_use=NULL ) {
			if( empty( $values_to_use ) ) {
				$this->debug = '<div class="error">';
				$this->debug .= '<p>' . __( 'The form to save the Extended Super Admin options was submitted, but the values were empty. Therefore, nothing was updated.', ESA_TEXT_DOMAIN ) . '</p>';
				$this->debug .= '</div>';
				return;
			}
			
			/*$this->set_options( $values_to_use );
			$this->options = $this->get_options();*/
			
			if( update_site_option( ESA_OPTION_NAME, $values_to_use ) ) {
				$this->debug = '<div class="updated">';
				$this->debug .= '<p>' . __( 'The options for the Extended Super Admins plugin have been updated.', ESA_TEXT_DOMAIN ) . '</p>';
				$this->debug .= '</div>';
			} else {
				$this->debug .= '<div class="error">';
				$this->debug .= '<p>' . __( 'There was an error committing the changes to the database.', ESA_TEXT_DOMAIN ) . '</p>';
				$this->debug .=  '</div>';
			}
			unset( $this->options );
			$this->set_options();
			return $this->options;
		}
		
		function verify_options( $input=NULL ) {
			/*global $site_id;
			$this->debug .= '<p>We are attempting to update the options for network ' . $site_id . '</p>';*/
			if( empty( $input ) && isset( $_POST['esa_options_action'] ) )
				$input = $_POST;
			
			$this->set_options( $input );
			$this->options = $this->get_options();
			return $this->options;
		}
		
		/**
		 * Remove items from the array of options
		 */
		function unset_options( $ids=array() ) {
			$keys = array( 'role_name', 'role_caps', 'role_members' );
			
			foreach( $ids as $id ) {
				unset( $this->role_id[$id], $this->role_name[$id], $this->role_caps[$id], $this->role_members[$id] );
			}
		}
		
		/**
		 * Remove this plugin's settings from the database
		 */
		function delete_settings() {
			delete_site_option( ESA_OPTION_NAME );
			return print('<div class="warning"><p>' . __( 'The settings for this plugin have been deleted.', ESA_TEXT_DOMAIN ) . '</p></div>');
		}
		
		/**
		 * Perform the action of revoking specific privileges from the current user
		 */
		function revoke_privileges( $caps, $cap, $user_id, $args ) {
			if( 'manage_esa_options' == $cap )
				$this->perms_checked = true;
				
			if( !is_super_admin() ) {
				if( 'manage_esa_options' == $cap )
					return array_merge( $caps, array( 'do_not_allow' ) );
				
				return $caps;
			}
			
			global $current_user;
			get_currentuserinfo();
			$role_id = NULL;
			
			foreach( $this->role_members as $id=>$members ) {
				if( in_array( $current_user->user_login, $members ) ) {
					$role_id = $id;
					break;
				}
			}
			
			if( is_null( $role_id ) )
				return $caps;
			
			if( !is_array( $this->role_caps[$role_id] ) || !array_key_exists( $cap, $this->role_caps[$role_id] ) )
				return $caps;
				
			return array_merge( $caps, array( 'do_not_allow' ) );
		}
		
		function add_settings_meta_boxes() {
			$output = '';
			foreach( $this->role_id as $id ) {
				/*print("\n<!-- We are adding a new meta box for the item with an ID of $id -->\n");*/
				if( function_exists( 'add_meta_box' ) ) {
					add_meta_box( 'esa-options-meta-' . $id, $this->role_name[$id], array( $this, 'make_settings_meta_boxes' ), ESA_OPTIONS_PAGE, 'advanced', 'low', array( 'id' => $id ) );
				} else {
					wp_die( 'While trying to create the existing role meta boxes, we found that the meta box function does not exist' );
					$output .= $this->admin_options_section( $id );
				}
			}
			if( function_exists( 'add_meta_box' ) ) {
				if( empty( $this->role_id ) ) {
					$id = 1;
				} else {
					$id = ( max( $this->role_id ) + 1 );
				}
				/*print("\n<!-- We are adding a new meta box for a new role -->\n");*/
				add_meta_box( 'esa-options-meta-' . $id, __( 'Add a New Role', ESA_TEXT_DOMAIN ), array( $this, 'make_settings_meta_boxes' ), ESA_OPTIONS_PAGE, 'normal', 'high', array( 'id' => NULL ) );
			} else {
				wp_die( 'While trying to create a meta box for the new role, we found that the meta box function does not exist' );
				$output .= $this->admin_options_section();
			}
			
			if( !function_exists( 'add_meta_box' ) )
				return $output;
			else
				return NULL;
		}
		
		function _save_settings_options() {
			global $wp_version;
			/* We need to save our options if the form was already submitted */
			if( isset( $_POST['esa_options_action'] ) && wp_verify_nonce( $_POST['_wp_nonce'], 'esa_options_save' ) ) {
				if( $this->save_options( $_POST ) ) {
					if( version_compare( $wp_version, '3.0.9', '>' ) )
						wp_redirect( network_admin_url('settings.php?page=esa_options_page&action-message=updated') );
					else
						wp_redirect( admin_url('ms-admin.php?page=esa_options_page&action-message=updated') );
				} else {
					if( version_compare( $wp_version, '3.0.9', '>' ) )
						wp_redirect( network_admin_url('settings.php?page=esa_options_page&action-message=failed') );
					else
						wp_redirect( admin_url('ms-admin.php?page=esa_options_page&action-message=failed') );
				}
			} elseif( isset( $_POST['esa_options_action'] ) ) {
				$this->debug .= '<div class="warning">';
				$this->debug .= __( 'The nonce for these options could not be verified.', ESA_TEXT_DOMAIN );
				$this->debug .= '</div>';
			}
		}
		
		function admin_options_page() {
			if( !$this->current_user_can( 'manage_esa_options' ) ) {
				if( $this->_use_log )
					error_log( '[ESA Notice]: The current user was determined not to have the right cap to view the admin settings page.' );
?>
<div class="wrap">
	<h2><?php _e('Extended Super Admin Settings', ESA_TEXT_DOMAIN) ?></h2>
    <p><?php _e('You do not have the appropriate permissions to modify the settings for this plugin. Please work with the network owner to update these settings.', ESA_TEXT_DOMAIN) ?></p>
</div>
<?php
				return;
			}
			if( isset( $_REQUEST['options-action'] ) ) {
				if( stristr( $_REQUEST['options-action'], 'multi_network_' ) ) {
					require_once( ESA_ABS_DIR . '/inc/multi_network_activation.php' );
					return;
				} elseif( 'remove_settings' == $_REQUEST['options-action']) {
					return $this->delete_settings();
				} elseif( 'flush-codex-cache' == $_REQUEST['options-action'] ) {
					if( false !== $this->flush_codex_cache() )
						echo '<div class="updated"><p>' . __( 'The codex information was flushed and repopulated.', ESA_TEXT_DOMAIN ) . '</p></div>';
				}
			}
			
			/* Start our output */
			$output = '
	<div class="wrap">';
			$output .= '
		<h2>' . __( 'Extended Super Admin Settings', ESA_TEXT_DOMAIN ) . '</h2>';
			if( isset( $_REQUEST['action-message'] ) ) {
				if( 'updated' == $_REQUEST['action-message'] )
					$output .= '<div class="updated"><p>' . __( 'The options for this plugin were updated successfully.' ) . '</p></div>';
				else
					$output .= '<div class="error"><p>' . __( 'There was an error updating the options for this plugin.' ) . '</p></div>';
			}
			$output .= '
		<div id="poststuff" class="metabox-holder">
			<div id="post-body">
				<div id="post-body-content">';
			if( !empty( $this->debug ) ) {
				$output .= $this->debug;
				unset( $this->debug );
			}
			$output .= '<p><em>' . __( 'In the lists of capabilities below, wherever (?) appears, you can click on that to view information from the WordPress Codex about that specific capability. The information is retrieved from the Codex once a week.', ESA_TEXT_DOMAIN ) . '</em></p><p><em>' . sprintf( __( 'Don\'t like the description? <a href="%s">Login to the WordPress Codex and edit it.</a>', ESA_TEXT_DOMAIN ), 'http://codex.wordpress.org/Roles_and_Capabilities' ) . '</em></p>';
			$output .= '
		<form method="post" action="">';
			$output .= wp_nonce_field( 'esa_options_save', '_wp_nonce', true, false );
			$output .= wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false, false );
			$output .= wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false, false );
			
			echo $output;
			$output = '';
			/* Output a set of option fields for each role that's already been created */
			if( !function_exists( 'add_meta_box' ) ) {
				wp_die( 'While outputting the admin page, we found that the meta box function does not exist' );
				$output .= $this->add_settings_meta_boxes();
			}
			if( empty( $output ) ) {
				do_meta_boxes( ESA_OPTIONS_PAGE, 'normal', NULL );
				do_meta_boxes( ESA_OPTIONS_PAGE, 'advanced', NULL );
			} else {
				echo $output;
			}
			
			$output = '';
			
			$output .= sprintf( __( '<p>Don\'t see the user you were looking for listed in the "%s" box above? Only existing Super Admins show up in that list, so you will need to grant that user Super Admin privileges before you can choose them from the list.</p>', ESA_TEXT_DOMAIN ), __( 'Users That Should Have This Role', ESA_TEXT_DOMAIN ) );
			
			if( $this->is_multi_network ) {
				$output .= __( '<p>When you save these options, they will be saved across all of your networks. At this time, there is no way to save any options individually per network. Unfortunately, that means that there is currently no way to grant a user unfettered Super Admin privileges on one network while restricting their Super Admin privileges on another network. It is all or nothing. That said, though, this plugin does not grant any extra privileges to anyone. If there is a network on which they are not set up as a Super Admin, they will still not be granted any Super Admin privileges on that network when you save these options.</p><p>There are plans to allow these changes to be saved on individual networks in the future, but it just has not reached that point, yet.</p>', ESA_TEXT_DOMAIN );
			}
			
			if( is_array( $this->caps_descriptions ) )
				$output .= '<div id="caps_container">' . implode( "\n", $this->caps_descriptions ) . '</div>';
			$output .= '
			<p class="submit">
	        	<input type="submit" class="button-primary" value="' . __('Save', ESA_TEXT_DOMAIN) . '"/>
			</p>
			<input type="hidden" name="esa_options_action" value="save"/>
		</form>
		<p>
			<a href="' . network_admin_url( 'settings.php?page=' . $_REQUEST['page'] . '&amp;options-action=flush-codex-cache' ) . '" class="button">' . __( 'Flush the Cache of Codex Info', ESA_TEXT_DOMAIN ) . '</a>
		</p>';
			$output .= '
					<br style="clear: both;" />
				</div><!-- #post-body-content -->
			</div><!-- #post-body -->
		</div><!-- #poststuff --><br class="clear">
	</div><!-- .wrap -->';
			echo $output;
		}
		
		function admin_options_section( $id=NULL ) {
			$new = false;
			if( !empty( $id ) ) {
				$role_id = $id;
			} else {
				$new = true;
				if( empty( $this->role_id ) ) {
					$id = 1;
				} else {
					$id = ( max( $this->role_id ) + 1 );
				}
				$role_id = $id;
				$this->role_id[$id] = $id;
				$this->role_name[$id] = '';
				$this->role_members[$id] = array();
				$this->role_caps[$id] = array();
			}
			$output = '
			<table class="form-table esa-options-table" id="esa-options-table-' . $id . '">
				<thead>
					<tr>
						<th' . ( ( $new ) ? ' colspan="2"' : '' ) . '>
							<h3>' . ( ( $new ) ? 'Add a New Role' : $this->role_name[$id] ) . '</h3>
							<input type="hidden" name="role_id[' . $id . ']" id="role_id_' . $id . '" value="' . $id . '"/>
						</th>' . ( ( $new ) ? '' : '
						<td>
							<label for="delete_role_' . $id . '">' . __( 'Would you like this role to be deleted?', ESA_TEXT_DOMAIN ) . '</label> <input type="checkbox" value="on" name="delete_role[' . $id . ']" id="delete_role_' . $id . '"/>
						</td>' ) . '
					</tr>
				</thead>
				<tbody id="esa_options_' . $id . '">';
			$output .= $this->role_name_options( $id );
			$output .= $this->role_caps_options( $id );
			$output .= $this->role_members_options( $id );
			$output .= '
				</tbody>
			</table>';
			return $output;
		}
		
		function make_settings_meta_boxes() {
			$id = NULL;
			
			$func_args = func_get_args();
			if( is_array( $func_args ) )
				$func_args = array_pop( $func_args );
			if( is_array( $func_args ) && array_key_exists( 'args', $func_args ) )
				$args = $func_args['args'];
			if( is_array( $args ) )
				$id = array_shift( $args );
			unset( $args, $func_args );
			
			$new = false;
			if( !empty( $id ) ) {
				$role_id = $id;
			} else {
				$new = true;
				if( empty( $this->role_id ) ) {
					$id = 1;
				} else {
					$id = ( max( $this->role_id ) + 1 );
				}
				$role_id = $id;
				$this->role_id[$id] = $id;
				$this->role_name[$id] = '';
				$this->role_members[$id] = array();
				$this->role_caps[$id] = array();
			}
			
			$delchkbx = ($new) ? '' : '<label for="delete_role_' . $id . '">' . __( 'Would you like this role to be deleted?', ESA_TEXT_DOMAIN ) . '</label> <input type="checkbox" value="on" name="delete_role[' . $id . ']" id="delete_role_' . $id . '"/>';
			
			$output = '
			<table class="form-table esa-options-table" id="esa-options-table-' . $id . '">';
			$output .= ( empty( $delchkbx ) ) ? '' : '
				<thead>
					<tr>
						<th>&nbsp;</th><td>' . $delchkbx . '</td>
					</tr>
				</thead>';
			$output .= '
				<tbody id="esa_options_' . $id . '">';
				$output .= $this->role_name_options( $id );
				$output .= $this->role_caps_options( $id );
				$output .= $this->role_members_options( $id );
				$output .= '
				</tbody>
			</table>';
			echo $output;
		}
		
		function role_name_options( $id=NULL ) {
			if( is_null( $id ) )
				return;
			
			$output = '
					<tr style="vertical-align: top">
						<th scope="row">
							<label for="role_name_' . $id . '">' . __( 'Name of Role:', ESA_TEXT_DOMAIN ) . '</label>
						</th>';
			$output .= '
						<td>
							<input type="hidden" name="role_id[' . $id . ']" id="role_id_' . $id . '" value="' . $id . '"/>
							<input type="text" name="role_name[' . $id . ']" id="role_name_' . $id . '" value="' . ( ( array_key_exists( $id, $this->role_name ) ) ? $this->role_name[$id] : '' ) . '"/>
						</td>
					</tr>';
			return $output;
		}
		
		function role_caps_options( $id=NULL ) {
			if( is_null( $id ) )
				return;
			
			$allcaps = array_filter( array_keys( $this->get_allcaps() ), array( $this, 'remove_numeric_keys' ) );
			
			$output = '
					<tr style="vertical-align: top">
						<th scope="row">
							' . __( 'Capabilities to <strong>Remove</strong> From This Role', ESA_TEXT_DOMAIN ) . '
						</th>
						<td>';
			foreach( $allcaps as $cap ) {
				$output .= '
							<div class="checkbox-container">';
				$output .= '
								<input type="checkbox" name="role_caps[' . $id . '][' . $cap . ']" id="role_caps_' . $id . '_' . $cap . '" value="on"' . checked( $this->role_caps[$id][$cap], 'on', false ) . '/>';
				$output .= '
								<label for="role_caps_' . $id . '_' . $cap . '">' . $cap . '</label>';
				if( !function_exists( 'getCodexCapabilities' ) )
					require_once( 'inc/retrieve-capabilities-info.php' );
				
				if( is_array( $this->caps_descriptions ) ) {
					$tmp = getCodexCapabilities();
					if( is_array( $tmp ) )
						$this->caps_descriptions = array_merge( $this->caps_descriptions, getCodexCapabilities() );
				}
				
				if( !empty( $this->caps_descriptions ) && array_key_exists( $cap, $this->caps_descriptions ) ) {
					$output .= ' <span class="caps_info_hover" id="caps_info_hover_' . $id . '_' . $cap . '">(?)</span>';
				}
				$output .= '
							</div>';
			}
			$output .= '
						</td>
					</tr>';
			
			return $output;
		}
		
		function role_members_options( $id=NULL ) {
			if( is_null( $id ) )
				return;
			
			if( !is_array( $this->role_members[$id] ) ) {
				$this->role_members[$id] = array( 0 => '' );
			}
			$output = '
					<tr style="vertical-align: top">
						<th scope="row">
							<label for="role_members_' . $id . '">' . __( 'Users That Should Have This Role', ESA_TEXT_DOMAIN ) . '</label>
						</th>';
			$superadmins = $this->get_super_admin_list();
			$output .= '
						<td>
							<select name="role_members[' . $id . '][]" id="role_members_' . $id . '" multiple="multiple" class="role-members-select" size="10">';
			foreach( $superadmins as $admin ) {
				$sel = ( in_array( $admin, $this->role_members[$id] ) ) ? ' selected="selected"' : '';
				$output .= '
							<option value="' . $admin . '"' . $sel . '>' . $admin . '</option>';
			}
			$output .= '
							</select>
						</td>
					</tr>';
			
			return $output;
		}
		
		function get_super_admin_list() {
			return get_super_admins();
		}
		
		function get_allcaps() {
			if( !empty( $this->allcaps ) )
				return $this->allcaps;
			
			global $current_user;
			get_currentuserinfo();
			if( !empty( $current_user->allcaps ) && count( $current_user->allcaps ) > 1 )
				$this->allcaps = $current_user->allcaps;
			
			if( empty( $this->allcaps ) ) {
				$this->allcaps = array();
				$roles = new WP_Roles;
				$roles = $roles->roles;
				foreach( $roles as $name=>$role ) {
					$this->allcaps = array_merge( $role['capabilities'], $this->allcaps );
				}
			}
			$multisitecaps = array(
				'manage_network'         => 1,
				'manage_network_plugins' => 1,
				'manage_network_users'   => 1,
				'manage_network_themes'  => 1,
				'manage_network_options' => 1,
				'manage_sites'           => 1,
				'manage_esa_options'     => 1,
				'update_core'            => 1, 
				'list_users'             => 1, 
				'remove_users'           => 1, 
				'promote_users'          => 1, 
				'add_users'              => 1, 
				'delete_themes'          => 1, 
				'export'                 => 1, 
				'edit_comment'           => 1, 
				'edit_plugins'           => 1, 
				'edit_themes'            => 1, 
				'update_plugins'         => 1, 
				'update_themes'          => 1, 
			);
			$this->allcaps = array_merge( $this->allcaps, $multisitecaps );
			
			ksort( $this->allcaps );
			
			return $this->allcaps;
		}
		
		function remove_numeric_keys($var) {
			return !is_numeric( $var );
		}
		
		function add_submenu_page() {
			global $wp_version;
			$options_page = ( version_compare( $wp_version, '3.0.9', '>' ) ) ? 'settings.php' : 'ms-admin.php';
			/* Add the new options page to the Super Admin menu */
			$rt = add_submenu_page( 
				/*$parent_slug = */$options_page, 
				/*$page_title = */'Extended Super Admin Settings', 
				/*$menu_title = */'Extended Super Admin', 
				/*$capability = */'manage_esa_options', 
				/*$menu_slug = */ESA_OPTIONS_PAGE, 
				/*$function = */array($this, 'admin_options_page')
			);
		}
		
		function flush_codex_cache() {
			return delete_site_transient( '_esa_capsCodexInfo' );
		}
	}
}
?>