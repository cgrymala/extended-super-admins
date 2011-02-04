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

if( $_GET['options-action'] == 'multi_network_activate' ) {
	if( !function_exists( 'wpmn_network_exists' ) )
		require( WP_PLUGIN_DIR . '/wp-multi-network/wp-multi-network.php' );
	if( !class_exists( 'wpmn_super_admins' ) ) {
		require( ESA_ABS_DIR . '/' . 'class-extended_super_admins.php' );
		$wpmn_super_admins_obj = new wpmn_super_admins;
	}
	
	$networks = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT id FROM ' . $wpdb->site ) );
	if( count( $networks ) ) {
		foreach( $networks as $network ) {
			$opts_updated = false;
			wpmn_switch_to_network( $network->id );
			if( current_user_can( 'manage_esa_options' ) ) {
				$asp = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
				if( !array_key_exists( ESA_PLUGIN_BASENAME, $asp ) ) {
					$asp = array_merge( $asp, array( ESA_PLUGIN_BASENAME => time() ) );
					update_site_option( 'active_sitewide_plugins', $asp );
					if( !isset( $wpmn_super_admins_obj ) ) {
						$wpmn_super_admins_obj = new wpmn_super_admins();
						$opts_updated = true;
					} else {
						$wpmn_super_admins_obj->set_options();
						$opts_updated = true;
					}
					if( $opts_updated ) {
						echo '<p>';
						printf( __( 'The Extended Super Admins options were successfully updated for the network with an ID of %d, as well.', ESA_TEXT_DOMAIN ), $network->id );
						echo '</p>';
					}
					echo '<p>' . __( 'The Extended Super Admins plug-in was successfully network-activated on the network with an ID of ', ESA_TEXT_DOMAIN ) . $network->id . '</p>';
				} else {
					echo '<p>';
					printf( __( 'The Extended Super Admins plug-in was already network-active on the network with an ID of %d, therefore, no changes were made.', ESA_TEXT_DOMAIN ), $network->id );
					echo '</p>';
				}
			} else {
				echo '<p>' . __( 'You do not have the appropriate permissions to network activate this plug-in on the network with an ID of ', ESA_TEXT_DOMAIN ) . $network->id . '</p>';
			}
			wpmn_restore_current_network();
		}
		echo '</div>';
	} else {
		echo '<p>' . __( 'Multiple networks could not be found, therefore, no additional changes were made.', ESA_TEXT_DOMAIN ) . '</p>';
		echo '</div>';
	}
} elseif( $_GET['options-action'] == 'multi_network_deactivate' ) {
	if( !function_exists( 'wpmn_network_exists' ) )
		require( WP_PLUGIN_DIR . '/wp-multi-network/wp-multi-network.php' );
	if( !class_exists( 'wpmn_super_admins' ) )
		require( ESA_ABS_DIR . '/class-' . 'wpmn_super_admins.php' );
	
	$networks = $wpdb->get_results( $wpdb->prepare( 'SELECT DISTINCT id FROM ' . $wpdb->site ) );
	if( count( $networks ) ) {
		foreach( $networks as $network ) {
			wpmn_switch_to_network( $network->id );
			if( current_user_can( 'manage_esa_options' ) ) {
				$asp = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );
				if( array_key_exists( ESA_PLUGIN_BASENAME, $asp ) ) {
					unset( $asp[ESA_PLUGIN_BASENAME] );
					/*$asp = array_splice( $asp, array_search( $asp[ADAUTHINT_PLUGIN_BASENAME], $asp ), 1 );*/
					update_site_option( 'active_sitewide_plugins', $asp );
					echo '<p>' . __( 'The Extended Super Admins plug-in was successfully deactivated for the network with an ID of ', ESA_TEXT_DOMAIN ) . $network->id . '</p>';
				} else {
					echo '<p>';
					printf( __( 'The Extended Super Admins plug-in was not network-active on the network with an ID of %d, therefore, no changes were made.', ESA_TEXT_DOMAIN ), $network->id );
					echo '</p>';
				}
			} else {
				echo '<p>' . __( 'You do not have the appropriate permissions to network deactivate this plug-in on the network with an ID of ', ESA_TEXT_DOMAIN ) . $network->id . '</p>';
			}
			wpmn_restore_current_network();
		}
		echo '</div>';
	} else {
		echo '<p>' . __( 'Multiple networks could not be found, therefore, no additional changes were made.', ESA_TEXT_DOMAIN ) . '</p>';
		echo '</div>';
	}
}