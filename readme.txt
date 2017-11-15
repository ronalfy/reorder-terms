=== Reorder Terms ===
Contributors: ronalfy, bigwing
Author URI: https://github.com/ronalfy/reorder-terms
Plugin URL: https://wordpress.org/plugins/reorder-terms/
Requires at Least: 4.6
Tested up to: 4.9
Tags: reorder, reorder terms
Stable tag: 1.1.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A simple and easy way to reorder your terms in WordPress.

== Description ==

We consider Reorder Terms a <strong>developer tool</strong>. If you do not know what `menu_order` or custom queries are, then this plugin is likely not for you.  This is an add-on to <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts</a> and requires <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts 2.1.0 or greater</a>.

[youtube https://www.youtube.com/watch?v=C_dmk9ApGGc]

Reorder Terms takes a different approach to term reordering. Instead of modifying core tables to achieve reordering, we do it using term meta per post type.

With the ability to add taxonomies to multiple post types, this method allows you to reorder terms within each post type attached to the same taxonomy.

This plugin treats terms like pages. Each term in a hierarchy has a term order. This allows quick reordering and deep traversing to get the exact terms and order you prefer.

As a result, you can get reordered terms with a query such as:

`
$query = array(
    'orderby' => 'meta_value_num',
    'order' => 'ASC',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => 'post_order',
            'compare' => 'NOT EXISTS'
        ),
        array(
            'key' => 'post_order',
            'value' => 0,
            'compare' => '>='
        )
    ),
    'hide_empty' => true,
    'parent' => 0   
);
$terms = get_terms( 'post_format', $query );
echo '<ul>';
foreach( $terms as $term ) {
	printf( '<li>%s</li>', esc_html( $term->name ) );
}
echo '</ul>';
`

While admittedly the query isn't exactly poetry, it's efficient, and insanely flexible.

<h3>Features</h3>
<li>Add-on to <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts</a>, so there is only one place to do all your reordering.</li>
<li>Reorder terms for each taxonomy within each post type. Very flexible.</li>
</ul>

<h3>Spread the Word</h3>
If you like this plugin, please help spread the word.  Rate the plugin.  Write about the plugin.  Something :)

<h3>Development</h3>
 
Development happens on GitHub.

You are welcome to help us out and <a href="https://github.com/ronalfy/reorder-terms">contribute on GitHub</a>.


== Installation ==

Either install the plugin via the WordPress admin panel, or ... 

1. Upload `reorder-terms` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

This plugin requires <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts 2.1.0 or greater</a>.

Please note that this plugin <strong>does not</strong> change the order of items in the front-end.  This functionality is <strong>not</strong> core WordPress functionality, so it'll require some work on your end to get the posts to display in your theme correctly.


<a href="https://github.com/ronalfy/reorder-terms#usage">See usage for some examples.</a>

== Frequently Asked Questions ==

Ask your questions here!

== Screenshots ==
1. Reorder Terms Interface
2. Example of Terms on the front-end
3. Example of Terms on the front-end

== Changelog ==

= 1.1.0 =
* Re-release

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Backwards incompatible re-release.

= 1.0.0 =
Initial Release