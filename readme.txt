=== Reorder Terms ===
Contributors: ronalfy, bigwing
Author URI: https://github.com/ronalfy/reorder-by-term
Plugin URL: https://wordpress.org/plugins/reorder-by-term/
Requires at Least: 4.4
Tested up to: 4.4
Tags: reorder, re-order, posts, terms, taxonomies, term, taxonomy, post type, post-type, ajax, admin, menu_order, ordering
Stable tag: 1.0.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A simple and easy way to reorder your custom post types within terms in WordPress.

== Description ==

We consider Reorder Terms a <strong>developer tool</strong>. If you do not know what `menu_order` or custom queries are, then this plugin is likely not for you.  This is an add-on to <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts</a> and requires <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts 2.1.0 or greater</a>.

Out of the box, WordPress does not support term meta. With WordPress 4.4, we can reach WordPress nirvana as many things, including this plugin, were not possible before.

This plugin simply allows you to select terms within the context of a post type that you can query and reorder.

For example, if you have one taxonomy attached to two different post types, would you not want to reorder both?

With this plugin, you can. You just add an extra options to `get_terms`:
`orderby=>{post_type}_order`

<h3>Features</h3>
<li>Add-on to <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts</a>, so there is only one place to do all your reordering.
Reorder by taxonomy within post type.
</ul>

<h3>Spread the Word</h3>
If you like this plugin, please help spread the word.  Rate the plugin.  Write about the plugin.  Something :)

<h3>Translations</h3>
 None so far.
 
 If you would like to contribute a translation, please leave a support request with a link to your translation.
 
 <h3>Development</h3>
 
 Development happens on GitHub.

You are welcome to help us out and <a href="https://github.com/ronalfy/reorder-terms">contribute on GitHub</a>.

<h3>Support</h3>

If you require immediate feedback, feel free to @reply me on Twitter with your support link:  <a href="https://twitter.com/ronalfy">@ronalfy</a>.  Support is always free unless you require some advanced customization out of the scope of the plugin's existing features.   Please rate/review the plugin if we have helped you to show thanks for the support.


== Installation ==

Either install the plugin via the WordPress admin panel, or ... 

1. Upload `reorder-terms` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

This plugin requires <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts 2.1.0 or greater</a>.

Please note that this plugin <strong>does not</strong> change the order of items in the front-end.  This functionality is <strong>not</strong> core WordPress functionality, so it'll require some work on your end to get the posts to display in your theme correctly.

You'll want to make use of <a href="http://codex.wordpress.org/Class_Reference/WP_Query">WP_Query</a>, <a href="http://codex.wordpress.org/Template_Tags/get_posts">get_posts</a>, or <a href="http://codex.wordpress.org/Plugin_API/Action_Reference/pre_get_posts">pre_get_posts</a> to modify query behavior on the front-end of your site.

<a href="https://github.com/ronalfy/reorder-terms#usage">See usage for some examples.</a>

== Frequently Asked Questions ==

Ask your questions here!

== Screenshots ==

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial Release