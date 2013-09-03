=== Extended Super Admins ===
Contributors: cgrymala
Donate link: http://www.umw.edu/gift/make_a_gift/default.php
Tags: wpmu, multisite, super admins, site admin, role manager, capabilities, wpmn
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 0.6.1

This plugin allows you to create multiple levels of Super Admins in a multi-site configuration.

== Description ==
The way that WordPress roles and capabilities are set up, there is only level of Super Admin available. Without this plugin, the only way to grant a user control over certain aspects of the network, you have to grant that user control over all aspects.

This plugin allows you to revoke specific privileges from any Super Admin on the network. You can create new roles within the site, then assign any Super Admin to that role; effectively removing the appropriate privileges.

This plugin does not grant any new privileges (with the possible exception of the ability to manage the settings for this plugin itself) to any users. It is only capable of removing privileges.

This plugin is also built to be compatible with the [WordPress Multi Network](http://wordpress.org/extend/plugins/wp-multi-network/) plugin and the [Networks for WordPress](http://wordpress.org/extend/plugins/networks-for-wordpress/) plugin. If either of those plugins is active, the options for this plugin will be saved and used for all of the networks within the installation.

This plugin was developed by [Curtiss Grymala](http://wordpress.org/support/profile/cgrymala) for the [University of Mary Washington](http://umw.edu/). It is licensed under the GPL2, which basically means you can take it, break it and change it any way you want, as long as the original credit and license information remains somewhere in the package.

== Important Notice ==
It is entirely possible that there could be serious bugs when used in different settings. At this time, I am seeking people to test the plugin, so please report any issues you encounter. Thank you.

Also, if you are updating from the original public alpha of this plugin, you will need to delete the old version before installing this version. The folder name changed from extended_super_admins in the initial release to extended-super-admins in newer versions.

If you downloaded and installed this plugin from the WordPress repository, you will not need to do so, as the folder name changed when adding this plugin to that repository.

== Requirements ==
* This plugin requires WordPress. It might work with WordPressMU versions older than 3.0, but it has not been tested with those.

* This plugin requires WordPress to be setup in Multi Site mode. It will not do anything at all if the Multi Site functions are not enabled.

* This plugin also requires PHP5. Some attempt has been made to make it compatible with PHP4, but it has not been tested in that environment.

== To Do ==
* Make the Codex retrieval more efficient (find a better way to cache the information and a better way to compare it to Codex revisions)

* Continue to improve the UI for the plugin to make it as user-friendly as possible

== Known Issues ==
* On occasion, the JavaScript in this plugin might cause CPU usage to spike and freeze the browser momentarily

== Installation ==
1. Download and unzip the extended-super-admins package.
1. Upload the 'extended-super-admins' folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Promote any users that you want to manage through this plugin to Super Admin status
1. Begin creating new Super Admin roles on the settings page for this plugin, and assign the appropriate users to those new roles

== Frequently Asked Questions ==
= How do I let you know if I find a bug? =
Please either hit me up on [Twitter @cgrymala](http://twitter.com/cgrymala), [report the issue in the WordPress Support Forums](http://wordpress.org/tags/extended-super-admins?forum_id=10#postform) or [post a comment on my website](http://plugins.ten-321.com/extended-super-admins/#respond). If you e-mail me, please include "Extended Super Admins" in the subject line of the message. Thanks.

= Can I use this plugin to grant extra capabilities to user(s)? =
No. This plugin is only capable of removing privileges from a user. It cannot add extra privileges.

= Can I use this plugin on a non-Multi-Site WordPress? =
No. This plugin would be pointless for non-Multi-Site installations. It might actually generate errors if you try to use it on a site that doesn't have the multi-site capabilities turned on.

= Can a user belong to more than one role? =
At this time, you can assign a user to multiple roles, but only the first role in the list will have an effect on that user's capabilities. In a future version, I will look into allowing multiple roles to modify a user's capabilities.

= How do I know what the capabilities actually do? =
You can find descriptions of most of the capabilities in the WordPress codex. 

Unfortunately, there is no definitive explanation for each and every capability, so you may have to do some testing and play around with things a bit to get things working exactly the way you want.

= How does this plugin integrate with the WordPress Multi Network plugin from John James Jacoby or the Networks for WordPress plugin from David Dean? =
If either of those plugins is active, this plugin will attempt to use and save any and all of its settings across all of the networks. There is currently no way, with WPMN or Networks for WordPress activated, to use different settings on individual networks.

= How do I keep the users with the modified roles from being able to modify this plugin's settings? =
This plugin creates a new capability called "manage_esa_options". If you do not want a user to be able to modify the settings for this plugin, simply revoke that capability from any users that belong to the modified role(s).

== Screenshots ==
1. The admin area for this plugin. In this shot, two custom roles have been defined, and all three boxes have been collapsed.
2. The way the admin area looks while adding a new role.
3. The way the admin area looks while modifying an existing role. In this shot, an example of the Codex information is being displayed.

== Changelog ==
= 0.7 =
* Added multi-network support for [Networks+ plugin](http://wpebooks.com/networks/)
* Wrote custom function to switch networks in a multi-network setup
* Optimized the way Codex information is retrieved, stored and displayed
* Further optimized JavaScript
* Minified JavaScript and CSS files used in the plugin (non-minified source files are still included in the package)
* Added ability to manually flush the Codex information
* Extended length of Codex cache from 7 days to 30 days
* Minor change in CSS for 3.2
* Removed "Activate" and "Deactivate" links from plugins screen if user doesn't have the manage_esa_options capability
* Cleaned up improper uses of WPDB::prepare()
* Cleaned up messy SQL calls
* Removed useless ESA Manager role

= 0.6.1 =
* Hopefully fixed issue that allowed modified users to edit their own permissions
* Removed alpha indication from plugin version name

= 0.6a =
* The JavaScript for this plugin has been optimized quite a bit
* The admin options page now utilizes the standard WordPress meta box interface rather than using a custom interface (thanks to [RavanH](http://profiles.wordpress.org/users/RavanH/) for the suggestion)
* Updated the way the Codex dialogs are displayed on-screen

= 0.5a =
* Fixed bugs that caused roles not to be deleted properly if they had been added without all criteria specified

= 0.4a =
* Plugin now retrieves information about each capability (where available) from the WordPress Codex
* In addition to John James Jacoby's [WP Multi Network](http://wordpress.org/extend/plugins/wp-multi-network/) plugin, this plugin should now be compatible with David Dean's [Networks for WordPress](http://wordpress.org/extend/plugins/networks-for-wordpress/) plugin.
* Fixed bugs that caused roles not to be deleted properly
* Made minor UI changes to make it more obvious how roles are added and modified
* Added a notice warning users when a role is added without a name
* Special thanks to [RavanH](http://profiles.wordpress.org/users/RavanH/) for help testing and debugging this version

= 0.3a =
The "stable" copy of 0.2a was missing all subfolders. Corrected this issue.

= 0.2a =
Corrected a version inconsistency between the readme file and the main plugin file. No substantial changes were made to the functionality of this plugin.

= 0.1 =
This is the first version.

== Upgrade Notice ==
= 0.7 =
This version has been tested with 3.6.1, and fixes any warnings that may pop up about WPDB::prepare()

= 0.5a =
This version fixes multiple bugs again, and includes further improvements to the user interface. An update is highly recommended.

= 0.4a =
This version fixes multiple bugs, and includes improvements to the user interface. An update is recommended.

= 0.3a =
The previous version was missing all subfolders. This is a necessary upgrade to get the plugin working properly.