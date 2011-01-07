=== AuthAnvil WordPress Logon Agent ===
Contributors: Scorpionsoft
Donate Link: http://www.davidsuzuki.org/donate/
Tags: authentication,AuthAnvil,OTP,One-Time Password,token,password,security,login
Requires at least: 3.0.3
Tested up to: 3.0.4
Stable tag: 1.0

Two-Factor Authentication login security for your WordPress site using AuthAnvil.

== Description ==

This plugin enables two-factor authentication for WordPress using the AuthAnvil Strong Authentication Server.  
To use this plugin, you must have access to an AuthAnvil Server and have a user and a token configured.
The requirement to use AuthAnvil is set on a per-user basis, with the default set to not require two-factor authentication.  

== Installation ==

1. Unzip the plugin into your /wp-content/plugins/ directory.
2. Enter your AuthAnvil SAS and Site ID on the Settings -> AuthAnvil options page.
3. Enable or disable AuthAnvil Authentication on the Users -> Edit User page.

== Frequently Asked Questions ==

= Where can I find out more information about AuthAnvil? =

Learn about AuthAnvil at http://www.scorpionsoft.com/tour/intro

= Are there any special requirements for my WordPress/PHP installation? =

PHP5 or later.

= Does every user on my WordPress site require an AuthAnvil Token? =

No, since AuthAnvil authentication is enabled on a per user basis, you can require tokens only for the users that you feel need to be protected.

== Screenshots ==

1. AuthAnvil settings on the AuthAnvil settings page.
2. AuthAnvil options on the user's profile page.
3. AuthAnvil Passcode field added to the login page.
4. AuthAnvil SoftTokens.

== Changelog ==

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 1.0 =
Initial Release