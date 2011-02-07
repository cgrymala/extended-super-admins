<?php
/**
 * Includes the constants and defines the class for setting up multiple levels of super admins
 * @package WordPress
 * @subpackage ExtendedSuperAdmins
 * @since 0.1a
 * @version 0.4a
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
		var $caps_descriptions = array();
		
		/**
		 * Create our extended_super_admins object
		 */
		function __construct() {
			if( !is_multisite() || !is_user_logged_in() )
				return false;
			
			$this->set_options();
			add_role( 'esa_plugin_manager', 'Extended Super Admin Manager', array( 'manage_esa_options' ) );
			
			$this->can_manage_plugin();
			
			add_filter( 'map_meta_cap', array( $this, 'revoke_privileges' ), 0, 4 );
			add_action( 'network_admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
			add_action( 'init', array( $this, '_init' ) );
			add_filter('plugin_action_links_' . ESA_PLUGIN_BASENAME, array($this, 'add_settings_link'));
			
			return true;
		}
		
		function _init() {
			if( function_exists( 'load_plugin_textdomain' ) )
				load_plugin_textdomain( ESA_TEXT_DOMAIN, false, ESA_PLUGIN_PATH . '/lang/' );
				
			if( is_admin() ) {
				if( function_exists( 'register_setting' ) )
					register_setting( ESA_OPTION_NAME, ESA_OPTION_NAME, array( $this, 'verify_options' ) );
				if( function_exists( 'wp_enqueue_script' ) )
					wp_enqueue_script( 'esa_admin_scripts', plugins_url( 'scripts/extended_super_admins.js', __FILE__ ), array('jquery','jquery-ui-dialog'), '0.1', true );
				
				if( function_exists( 'wp_enqueue_style' ) ) {
					wp_register_style( 'jquery-ui-dialog', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/smoothness/jquery-ui.css', array(), '1.8.9', 'all' );
					wp_enqueue_style( 'esa_admin_styles', plugins_url( 'css/extended_super_admins.css', __FILE__ ), array('jquery-ui-dialog'), '0.1', 'all' );
				}
			}
		}
		
		function add_settings_link( $links ) {
			global $wp_version;
			$options_page = ( version_compare( $wp_version, '3.0.9', '>' ) ) ? 'settings' : 'ms-admin';
			$settings_link = '<a href="' . $options_page . '.php?page=' . ESA_OPTIONS_PAGE . '">';
			$settings_link .= __( 'Settings', ESA_TEXT_DOMAIN );
			$settings_link .= '</a>';
			
			array_unshift( $links, $settings_link );
			return $links;
		}
		
		/**
		 * Determine whether or not this user is allowed to modify this plugin's options
		 */
		function can_manage_plugin() {
			if( $this->perms_checked )
				return current_user_can( 'manage_esa_options' );
			
			global $current_user;
			get_currentuserinfo();
			
			if( is_super_admin() && current_user_can( 'manage_network_plugins' ) && current_user_can( 'manage_network_users' ) ) {
				$current_user->add_cap( 'manage_esa_options' );
				return true;
			} else {
				$current_user->remove_cap( 'manage_esa_options' );
				return false;
			}
		}
		
		/**
		 * Set the object's parameters
		 * @param array the values to use to set the options
		 */
		function set_options( $values_to_use=NULL ) {
			if( !empty( $values_to_use ) ) {
				$this->options = array(
					'role_id'		=> $values_to_use['role_id'],
					'role_name'		=> $values_to_use['role_name'],
					'role_members'	=> $values_to_use['role_members'],
					'role_caps'		=> $values_to_use['role_caps'],
				);
				foreach( $this->options['role_id'] as $id ) {
					if( ( empty( $this->options['role_name'][$id] ) && empty( $this->options['role_members'][$id] ) && empty( $this->options['role_caps'][$id] ) ) || ( isset($values_to_use['delete_role'][$id] ) && $values_to_use['delete_role'][$id] == 'on' ) ) {
						unset( $this->options['role_id'][$id], $this->options['role_name'][$id], $this->options['role_members'][$id], $this->options['role_caps'][$id] );
					}
				}
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
		 */
		function is_multi_network() {
			if( isset( $this->is_multi_network ) )
				return $this->is_multi_network;
			
			if( function_exists( 'wpmn_switch_to_network' ) ) {
				$this->is_multi_network = true;
			} else {
				$this->is_multi_network = false;
			}
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
		 * Perform the action of revoking specific privileges from the current user
		 */
		function revoke_privileges( $caps, $cap, $user_id, $args ) {
			if( $cap == 'manage_esa_options' )
				$this->perms_checked = true;
			
			if( !is_super_admin() ) {
				if( $cap == 'manage_esa_options' )
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
			
			if( !array_key_exists( $cap, $this->role_caps[$role_id] ) )
				return $caps;
				
			return array_merge( $caps, array( 'do_not_allow' ) );
		}
		
		function admin_options_page() {
			if( !current_user_can( 'manage_esa_options' ) ) {
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
				}
			}
			/* We need to save our options if the form was already submitted */
			if( isset( $_POST['esa_options_action'] ) && wp_verify_nonce( $_POST['_wp_nonce'], 'esa_options_save' ) ) {
				$this->save_options( $_POST );
			} elseif( isset( $_POST['esa_options_action'] ) ) {
				$this->debug .= '<div class="warning">';
				$this->debug .= __( 'The nonce for these options could not be verified.', ESA_TEXT_DOMAIN );
				$this->debug .= '</div>';
			}

			/* Start our output */
			$output = '
	<div class="wrap">';
			$output = '
		<h2>' . __( 'Extended Super Admin Settings', ESA_TEXT_DOMAIN ) . '</h2>';
			if( !empty( $this->debug ) ) {
				$output .= $this->debug;
				unset( $this->debug );
			}
			$output .= '
		<form method="post" action="">';
			$output .= wp_nonce_field( 'esa_options_save', '_wp_nonce', true, false );
			/* Output a set of option fields for each role that's already been created */
			foreach( $this->role_id as $id ) {
				$output .= $this->admin_options_section( $id );
			}
			$output .= $this->admin_options_section();
			$output .= '
			<p class="submit">
	        	<input type="submit" class="button-primary" value="' . __('Save and Add Another', ESA_TEXT_DOMAIN) . '"/>
			</p>
			<input type="hidden" name="esa_options_action" value="save"/>
		</form>';
			$output .= '
	</div>';
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
							<h3>' . ( ( $new ) ? 'New Role' : $this->role_name[$id] ) . '</h3>
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
		
		function role_name_options( $id=NULL ) {
			if( is_null( $id ) )
				return;
			
			$output = '
					<tr valign="top">
						<th scope="row">
							<label for="role_name_' . $id . '">' . __( 'Name of Role:', ESA_TEXT_DOMAIN ) . '</label>
						</th>';
			$output .= '
						<td>
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
					<tr valign="top">
						<th scope="row">
							' . __( 'Capabilities to Remove From This Role', ESA_TEXT_DOMAIN ) . '
						</th>
						<td>';
			foreach( $allcaps as $cap ) {
				$output .= '
							<div class="checkbox-container">';
				$output .= '
								<input type="checkbox" name="role_caps[' . $id . '][' . $cap . ']" id="role_caps_' . $id . '_' . $cap . '" value="on"' . checked( $this->role_caps[$id][$cap], 'on', false ) . '/>';
				$output .= '
								<label for="role_caps_' . $id . '_' . $cap . '">' . $cap . '</label>';
				if( !function_exists( 'findCap' ) )
					require_once( 'inc/retrieve-capabilities-info.php' );
				
				if( $caps_info = findCap( $cap, $this->caps_descriptions ) ) {
					$output .= '
								<div class="caps_info">';
					$this->caps_descriptions[$cap] = $caps_info;
					$output .= $this->caps_descriptions[$cap];
					$output .= '
								</div>';
				} else {
					$this->caps_descriptions[$cap] = false;
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
			
			$output = '
					<tr valign="top">
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
				'manage_network'			=> 1,
				'manage_network_plugins'	=> 1,
				'manage_network_users'		=> 1,
				'manage_network_themes'		=> 1,
				'manage_network_options'	=> 1,
				'manage_sites'				=> 1,
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
			add_submenu_page( 
				/*$parent_slug = */$options_page, 
				/*$page_title = */'Extended Super Admin Settings', 
				/*$menu_title = */'Extended Super Admin', 
				/*$capability = */'manage_esa_options', 
				/*$menu_slug = */ESA_OPTIONS_PAGE, 
				/*$function = */array($this, 'admin_options_page')
			);
		}
	}
}
?>