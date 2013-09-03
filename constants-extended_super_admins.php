<?php
/**
 * This file defines all the necessary constants for the Extended Super Admins plugin
 * @package WordPress
 * @subpackage ExtendedSuperAdmins
 * @since 0.1a
 * @version 0.7
 */

if ( ! defined( 'ESA_VERSION' ) )
	/**
	 * Define the current version of this plugin
	 */
	define( 'ESA_VERSION', '0.7a' );

if ( ! defined( 'ESA_OPTION_NAME' ) )
	/**
	 * Define the option name used to set/retrieve this plugin's settings from the database
	 */
	define( 'ESA_OPTION_NAME', 'extended_super_admins' );

if ( ! defined( 'ESA_PLUGIN_BASENAME' ) )
	/**
	 * Define the plugin_basename for this plugin
	 */
	define( 'ESA_PLUGIN_BASENAME', plugin_basename( str_replace( 'constants-', '', __FILE__ ) ) );

if ( ! defined( 'ESA_PLUGIN_PATH' ) )
	/**
	 * Define the directory name of this plugin
	 */
	define( 'ESA_PLUGIN_PATH', dirname( ESA_PLUGIN_BASENAME ) );

if ( ! defined( 'ESA_ABS_DIR' ) )
	/**
	 * Define the full path to this plugin directory
	 */
	define( 'ESA_ABS_DIR', ( ( stristr( __FILE__, 'mu-plugins' ) ) ? WPMU_PLUGIN_DIR : WP_PLUGIN_DIR ) . '/' . dirname(ESA_PLUGIN_BASENAME) );
	
if ( ! defined( 'ESA_OPTIONS_PAGE' ) )
	/**
	 * Define the name of the options page for WordPress to use
	 */
	define( 'ESA_OPTIONS_PAGE', 'esa_options_page' );

if ( ! defined( 'ESA_TEXT_DOMAIN' ) )
	/**
	 * Define the plugin_text_domain to be used by this plugin
	 */
	 define( 'ESA_TEXT_DOMAIN', 'esa_text_domain' );

if ( ! defined( 'ESA_CODEX_PAGE' ) )
	/**
	 * Define the API location for the codex information
	 * @since 0.7a
	 */
	define( 'ESA_CODEX_PAGE', 'http://codex.wordpress.org/api.php' );
	
if ( ! defined( 'ESA_CODEX_QUERY' ) )
	/**
	 * Define the API Query to retrieve the Codex information
	 * @since 0.7a
	 */
	define( 'ESA_CODEX_QUERY', '?action=query&prop=revisions&meta=siteinfo&titles=Roles_and_Capabilities&rvsection=12&rvprop=content|timestamp&format=php' );

if ( ! defined( 'ESA_CODEX_PARSE_QUERY' ) )
	/**
	 * Define the API Query to parse the Codex information
	 * @since 0.7a
	 */
	define( 'ESA_CODEX_PARSE_QUERY', '?format=php&action=parse&text=' );
?>