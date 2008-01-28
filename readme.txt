=== CryptX ===
Contributors: Ralf Weber
Donate link: http://weber-nrw.de/
Tags: encode, antispam, email, spam, spider, unicode, mailto, filter
Requires at least: 2.0
Tested up to: 2.3.2
Stable tag: 1.5

== Description ==

No more SPAM by spiders scanning you site for email adresses. With CryptX you can hide all your email adresses, 
with and without a mailto-link, by converting them using javascript or UNICODE. Although you can choose to add 
a mailto-link to all unlinked email adresses with only one klick at the settings. That's great, isn't it?

Thanks to Jeffrey Gould for finding a bug in the preg_match_all pattern fixed in Version 1.4!

New in Version 1.5: Template Tag 'cryptx'
Now you can use CryptX direct in your template by using the following syntax:
<?php cryptx( $content ,$LinkText ,$css ,$echo ); ?>

- $content: mail adress to encrypt
- $LinkText: Alternativ linktext. If not set, the mail adress will used as linktext. Default: not set
- $css: css class to ad to the mail link.
- $echo: Show the result or keep it in a variable. The default is true (1).

[Plugin Homepage](http://weber-nrw/wordpress/cryptx/ "Plugin Homepage")

== Installation ==

1. Upload "cryptX folder" to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit the Options under the Options Page.
4. Look at your Blog and be happy.

[Plugin Homepage](http://weber-nrw/wordpress/cryptx/ "Plugin Homepage")
