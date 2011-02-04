=== Extended Super Admins ===
Contributors: cgrymala
Tags: wpmu, multisite, super admins, role manager, capabilities, wpmn
Requires at least: 3.0
Tested up to: 3.1-RC3
Stable tag: 0.2a

This plugin allows you to create multiple levels of Super Admins in a multi-site configuration.

== Description ==

The way that WordPress roles and capabilities are set up, there is only level of Super Admin available. Without this plugin, the only way to grant a user control over certain aspects of the network, you have to grant that user control over all aspects.

This plugin allows you to revoke specific privileges from any Super Admin on the network. You can create new roles within the site, then assign any Super Admin to that role; effectively removing the appropriate privileges.

This plugin does not grant any new privileges (with the possible exception of the ability to manage the settings for this plugin itself) to any users. It is only capable of removing privileges.

This plugin is also built to be compatible with the WordPress Multi Network plugin. If the WPMN plugin is active, the options for this plugin will be saved and used for all of the networks within the installation.

== Important Notice ==

So far, this plugin has only been tested on a handful of WordPress installations; all my own. Therefore, it is entirely possible that there could be serious bugs when used in different settings. At this time, I am seeking people to test the plugin, so please report any issues you encounter. Thank you.

== Requirements ==

This plugin requires WordPress. It might work with WordPressMU versions older than 3.0, but it has not been tested with those.

This plugin requires WordPress to be setup in Multi Site mode. It will not do anything at all if the Multi Site functions are not enabled.

This plugin also requires PHP5. Some attempt has been made to make it compatible with PHP4, but it has not been tested in that environment.

== Installation ==

1. Download and unzip the extended_super_admins package.
1. Upload the 'extended_super_admins' folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Promote any users that you want to manage through this plugin to Super Admin status
1. Begin creating new Super Admin roles on the settings page for this plugin, and assign the appropriate users to those new roles

== Frequently Asked Questions ==

= How do I let you know if I find a bug? =

Please either hit me up on Twitter @cgrymala or e-mail me at cgrymala@umw.edu. If you e-mail me, please include "Extended Super Admins" in the subject line of the message. Thanks.

= Can I use this plugin to grant extra capabilities to user(s)? =

No. This plugin is only capable of removing privileges from a user. It cannot add extra privileges.

= Can I use this plugin on a non-Multi-Site WordPress? =

No. This plugin would be pointless for non-Multi-Site installations. It might actually generate errors if you try to use it on a site that doesn't have the multi-site capabilities turned on.

= Can a user belong to more than one role? =

At this time, you can assign a user to multiple roles, but only the first role in the list will have an effect on that user's capabilities. In a future version, I will look into allowing multiple roles to modify a user's capabilities.

= How do I know what the capabilities actually do? =

You can find descriptions of most of the capabilities in the WordPress codex. Unfortunately, there is no definitive explanation for each and every capability, so you may have to do some testing and play around with things a bit to get things working exactly the way you want.

= How does this plugin integrate with the WordPress Multi Network plugin from John James Jacoby? =

If the WPMN plugin is active, this plugin will attempt to use and save any and all of its settings across all of the networks. There is currently no way, with WPMN activated, to use different settings on individual networks.

== Changelog ==

= 0.2a =
Corrected a version inconsistency between the readme file and the main plugin file. No substantial changes were made to the functionality of this plugin.

= 0.1 =
This is the first version.
