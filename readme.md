Reorder Terms for WordPress
=============

A simple and easy way to reorder your custom post types within terms in WordPress.

Description
----------------------

Reorder Terms takes a different approach to term reordering. Instead of modifying core tables to achieve reordering, we do it using term meta per post type.

With the ability to add taxonomies to multiple post types, this method allows you to reorder terms within each post type attached to the same taxonomy.

This plugin treats terms like pages. Each term in a hierarchy has a term order. This allows quick reordering and deep traversing to get the exact terms and order you prefer.


Features
----------------------
Add-on to <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts</a>, so there is only one place to do all your reordering.

Use Cases
----------------------
Have a term archive for your posts and products separately.

Your imagination will give you more use-cases.  

Usage
----------------------

```php
/* Sample query and output */
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

```