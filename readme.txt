=== Shortcodify ===
Contributors: lrnz
Donate link: 
Tags: shortcode,shortcodes,tiny,rte,widget,widgets,menu,sitemap,menu,menus
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Shortcodify adds some useful Shortcodes to your blog.

== Description ==

Shortcodify adds some useful Shortcodes to your blog.

Shortcodes are small snippetts use in your textfield / tiny / rte field. In the live version of the page they will be replaced with something different.
One example for a shortcode of this plugin is `[date]` when you use it the tiny, in the live version you can see the actual Date.
But Shortcodify does more.
If you want to, Shortcodify creates a new widget area in, that you can use with the `[widget]` shortcode.

See some **possible shortcodes** below:

*   `[wsc class=myCustomClass] TEXT [/wsc]` - for individual CSS-classes
*   `[unshortcode] TEXT [/unshortcode]` - other shortcodes in this shortcode will be ignored
*   `[br]` - adds a linebreak (with clearing)
*   `[hr]` - adds a horizontal line
*   `[date]` - inserts the actual date
*   `[time]` -  inserts the actual time
*   `[widget]` - inserts the content of the "Shortcodify" widget area
*   `[random] Text1 Text2 [/random]` - returns only one of included areas (seperated by linebreak or whatever)
*   `[menu]` - adds a new menu to the nav-menus of WordPress. This shortcode inserts the content to the page for sitemaps or sitelist.
*   `[accordion]` - adds a jQueryIU accordion to the page 
*   `[section]` - adds a section to the accordion. Use it inside the `[accordion]` shortcode.
*   A few more useful shortcodes

= Is a useful shortcode missing? =
Please send me a request!

Version: 1.2.0

== Installation ==

1. Upload `shortcodify`-folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Have a look to the settings page.
4. Use the shortcodes in tiny-field.


== Frequently asked questions ==

= Do you have any questions? =

Please ask me.

== Screenshots ==

1. Shortcodify widget area.
2. Shortcodes in the tiny.
3. The output of the shortcodes

== Changelog ==
= 1.2.0 =
New shortcode: menu to insert pagelists or sitemaps

= 1.1.0 =
New shortcode: random

= 1.0.0 =
all new


== Upgrade notice ==
= 1.2.0 =
New shortcode: menu to insert pagelists or sitemaps
