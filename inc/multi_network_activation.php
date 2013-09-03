<?php
/**
 * Handle Multi-Network actions for the Extended Super Admins Plugin
 * @package WordPress
 * @subpackage ExtendedSuperAdmins
 * @since 0.1a
 */

if( !isset( $_GET['options-action'] ) )
	exit;
	
check_admin_referer( '_esa_multi_network' );

global $wpdb;

echo '
	<div class="wrap">
		<h2>' . __('Extended Super Admins - Multi-Network Activation') . '</h2>';

if( !class_exists( 'wpmn_super_admins' ) ) {
	require( ESA_ABS_DIR . '/' . 'class-wpmn_super_admins.php' );
}
$wpmn_super_admins_obj = new wpmn_super_admins;

if( 'multi_network_activate' == $_GET['options-action'] ) {
	$main_site_id = $wpdb->siteid;
	
	$networks = $wpdb->get_results( 'SELECT DISTINCT id FROM ' . $wpdb->site );
	
	if( count( $networks ) ) {
		$GLOBALS['esa_options'] = maybe_unserialize( get_site_option( ESA_OPTION_NAME, array(), false ) );
		$GLOBALS['force_esa_options_update'] = true;
		
		$original_site = array( 'site_id' => $GLOBALS['site_id'], 'blog_id' => $GLOBALS['blog_id'] );
		
		foreach( $networks as $network ) {
			if( $main_site_id == $network->id ) {
				print( '<p>' . __( sprintf( 'We skipped over the network with an ID of %d, because the plugin already appears to be network active on that site.', $network->id ), ESA_TEXT_DOMAIN ) . '</p>' );
				continue;
			}
			
			$output = '';
			
			$opts_updated = false;
			$wpmn_super_admins_obj->switch_to_site( $network->id );
			unset( $GLOBALS['previous_site'] );
			
			if( current_user_can( 'manage_esa_options' ) ) {
				$asp = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
				if( empty( $asp ) || !array_key_exists( ESA_PLUGIN_BASENAME, $asp ) ) {
					if( empty( $asp ) ) {
						$asp = array( ESA_PLUGIN_BASENAME => time() );
					} else {
						$asp = array_merge( $asp, array( ESA_PLUGIN_BASENAME => time() ) );
					}
					update_site_option( 'active_sitewide_plugins', $asp );
					if( !isset( $wpmn_super_admins_obj ) ) {
						$wpmn_super_admins_obj = new wpmn_super_admins();
						$opts_updated = true;
					} else {
						if( !empty( $GLOBALS['esa_options'] ) )
							$wpmn_super_admins_obj->set_options( $GLOBALS['esa_options'], $GLOBALS['force_esa_options_update'] );
						$opts_updated = true;
					}
					if( $opts_updated ) {
						$output .= '<p>' . sprintf( __( 'The Extended Super Admins options were successfully updated for the network with an ID of %d, as well.', ESA_TEXT_DOMAIN ), $network->id ) . '</p>';
					}
					$output = '<p>' . __( 'The Extended Super Admins plug-in was successfully network-activated on the network with an ID of ', ESA_TEXT_DOMAIN ) . $network->id . '</p>' . $output;
				} else {
					$output .= '<p>' . sprintf( __( 'The Extended Super Admins plug-in was already network-active on the network with an ID of %d, therefore, no changes were made.', ESA_TEXT_DOMAIN ), $network->id ) . '</p>';
				}
				
				echo $output;
			} else {
				echo '<p>' . __( 'You do not have the appropriate permissions to network activate this plug-in on the network with an ID of ', ESA_TEXT_DOMAIN ) . $network->id . '</p>';
			}
		}
		echo '</div>';
		
		$GLOBALS['previous_site'] = (object)$original_site;
		$wpmn_super_admins_obj->restore_current_site();
		
		unset( $GLOBALS['esa_options'], $GLOBALS['force_esa_options_update'] );
	} else {
		echo '<p>' . __( 'Multiple networks could not be found, therefore, no additional changes were made.', ESA_TEXT_DOMAIN ) . '</p>';
		echo '</div>';
	}
} elseif( 'multi_network_deactivate' == $_GET['options-action'] ) {
	$networks = $wpdb->get_results( 'SELECT DISTINCT id FROM ' . $wpdb->site );
	if( count( $networks ) ) {
		foreach( $networks as $network ) {
			$wpmn_super_admins_obj->switch_to_site( $network->id );
			if( current_user_can( 'manage_esa_options' ) ) {
				$asp = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
				if( !empty( $asp ) && array_key_exists( ESA_PLUGIN_BASENAME, $asp ) ) {
					unset( $asp[ESA_PLUGIN_BASENAME] );
					/*$asp = array_splice( $asp, array_search( $asp[ADAUTHINT_PLUGIN_BASENAME], $asp ), 1 );*/
					if( empty( $asp ) ) {
						delete_site_option( 'active_sitewide_plugins' );
					} else {
						update_site_option( 'active_sitewide_plugins', $asp );
					}
					echo '<p>' . __( 'The Extended Super Admins plug-in was successfully deactivated for the network with an ID of ', ESA_TEXT_DOMAIN ) . $network->id . '</p>';
				} else {
					echo '<p>';
					printf( __( 'The Extended Super Admins plug-in was not network-active on the network with an ID of %d, therefore, no changes were made.', ESA_TEXT_DOMAIN ), $network->id );
					echo '</p>';
				}
			} else {
				echo '<p>' . __( 'You do not have the appropriate permissions to network deactivate this plug-in on the network with an ID of ', ESA_TEXT_DOMAIN ) . $network->id . '</p>';
			}
			$wpmn_super_admins_obj->restore_current_site();
		}
		echo '</div>';
	} else {
		echo '<p>' . __( 'Multiple networks could not be found, therefore, no additional changes were made.', ESA_TEXT_DOMAIN ) . '</p>';
		echo '</div>';
	}
}