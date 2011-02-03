<?php
/*
Plugin Name: Extended Super Admins
Version: 0.1a
Plugin URI: http://plugins.ten-321.com/category/extended-super-admins/
Description: Enables the creation of multiple levels of "Super Admins" within WordPress Multi Site. Multiple new roles can be created, and all capabilities generally granted to a "Super Admin" can be revoked individually.
Author: Curtiss Grymala
Author URI: http://ten-321.com/
Network: true
License: GPL2

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
	if( !is_multisite() )
		return;
	if( function_exists( 'wpmn_switch_to_network' ) ) {
		require_once( 'class-wpmn_super_admins.php' );
		return new wpmn_super_admins;
	} else {
		require_once( 'class-extended_super_admins.php' );
		return new extended_super_admins;
	}
}
add_action( 'plugins_loaded', 'instantiate_extended_super_admins' );
?>