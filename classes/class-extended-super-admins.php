<?php
/**
 * Implements the Extended_Super_Admins class
 * @package extended-super-admins
 * @version 1.0
 */
class Extended_Super_Admins {
	/**
	 * The current version number of the plugin
	 * @var string
	 */
	public $version = '1.0';
	/**
	 * An array of the custom super admin roles created
	 * @var array
	 */
	public $roles   = array();
	/**
	 * An array to hold any options set by the plugin
	 * @var array
	 */
	public $options = array();
	/**
	 * An array of all of the capabilities in this WP install
	 * @var array
	 */
	public $allcaps = array();
	/**
	 * Indicates whether we've already checked this user's permissions
	 * @var bool
	 * @default false
	 */
	public $checked = false;
	/**
	 * The base name for this plugin
	 * @var string
	 */
	public $basename = null;
	
	/**
	 * Construct our Extended_Super_Admins object
	 */
	function __construct() {
		/**
		 * Bail out if this isn't multisite or the user is not logged in
		 */
		if ( ! is_multisite() || ! is_user_logged_in() )
			return;
		
		$this->basename = plugin_basename( plugin_dir_path( dirname( __FILE__ ) ) . '/extended-super-admins.php' );
		
		add_filter( 'map_meta_cap', array( $this, 'revoke_privileges' ), 0, 4 );
		add_action( 'network_admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'init', array( $this, '_init' ) );
		add_action( 'admin_init', array( $this, '_admin_init' ) );
		add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'add_settings_link' ) );
		add_filter( 'network_admin_plugin_action_links_' . $this->basename, array( $this, 'add_settings_link' ) );
	}
	
	/**
	 * Revoke any privileges that this user shouldn't have
	 */
	function revoke_privileges( $caps, $cap, $user_id, $args ) {
		if ( 'manage_esa_options' == $cap )
			$this->checked = true;
		
		if ( ! is_super_admin() ) {
			if ( 'manage_esa_options' == $cap ) {
				return array_merge( $caps, array( 'do_not_allow' ) );
			}
			
			return $caps;
		}
		
		global $current_user;
		get_currentuserinfo();
		$role_id = null;
		
		foreach ( $this->roles as $role ) {
			if ( in_array( $current_user->user_login, $role['members'] ) ) {
				$role_id = $role['id'];
				break;
			}
		}
		
		if ( is_null( $role_id ) )
			return $caps;
		
		if ( ! is_array( $this->roles[$role_id]['caps'] ) || ! array_key_exists( $cap, $this->roles[$role_id]['caps'] ) ) {
			return $caps;
		}
		
		return array_merge( $caps, array( 'do_not_allow' ) );
	}
	
	/**
	 * Create/register the admin settings page
	 */
	function add_submenu_page() {
	}
	
	/**
	 * Perform any actions that need to occur at init
	 */
	function _init() {
	}
	
	/**
	 * Perform any actions that need to occur at admin_init
	 */
	function _admin_init() {
	}
	
	/**
	 * Add some links to the plugin list page
	 */
	function add_settings_link() {
	}
}