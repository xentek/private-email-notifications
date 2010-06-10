=== Private Email Notifications ===
Contributors: xenlab
Donate link: http://j.mp/donate-to-xentek
Tags: privacy,email,notifications,comments,notify author,notify admin,wp_mail
Requires at least: 2.0
Tested up to: 2.9.2
Stable tag: trunk
 
Remove Email and IP address information from Email Notifications to protect the privacy of folks commenting on your blog.

== Description ==

Remove Email and IP address information from Email Notifications to protect the privacy of folks commenting on your blog. This is accomplished by overriding three pluggable functions and removing all email and IP address information from being sent out.

== Installation ==

1. Download the private-email-notifications.zip file, unzip and upload the whole directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Everything else is done for your automatically!

== Frequently Asked Questions ==

= Do I have control over the formatting of the email? =

Not at this time. The standard emails still go out, they just don't contain the email address or IP of the commenter.

= Which Pluggable functions does this plugin override? =
The following functions are overriden by this plugin:

* `wp_notify_postauthor`
* `wp_notify_moderator`
* `wp_new_user_notification`

Due to the way pluggable functions works, you can NOT have any other plugins trying to override these functions or they will clash. Some of these functions have actions/filters that you can use to further modify how they operate and in time I plan to add more of these hooks to my versions of these functions.

= I want to help with development of this Plugin! =

The project is now hosted on [github.com](http://github.com/xentek/private-email-notifications). Just fork the project and send me a pull request.

[New to git?](http://delicious.com/ericmarden/git)

== Changelog ==

= 0.4 =
* Initial Release after all debugging and roll out to a public site.

== License ==

The Private Email Notifications plugin was developed by Eric Marden, and is provided with out warranty under the GPLv2 License. More info and other plugins at: http://xentek.net

Copyright 2010  Eric Marden  (email : wp@xentek.net)

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA