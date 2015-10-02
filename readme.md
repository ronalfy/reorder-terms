Reorder Terms for WordPress
=============

A simple and easy way to reorder your custom post types within terms in WordPress.

Description
----------------------

We consider Reorder Terms a <strong>developer tool</strong>. If you do not know what `menu_order` or custom queries are, then this plugin is likely not for you.  This is an add-on to <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts</a> and requires <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts 2.1.0 or greater</a>.

Out of the box, WordPress does not support term meta. With WordPress 4.4, we can reach WordPress nirvana as many things, including this plugin, were not possible before.

This plugin simply allows you to select terms within the context of a post type that you can query and reorder.

For example, if you have one taxonomy attached to two different post types, would you not want to reorder both?

With this plugin, you can. You just add an extra options to `get_terms`:
`orderby=>{post_type}_order`


Features
----------------------
Add-on to <a href="https://wordpress.org/plugins/metronet-reorder-posts/">Reorder Posts</a>, so there is only one place to do all your reordering.
Reorder by taxonomy within post type.

Use Cases
----------------------
Have a term archive for your posts and products separately.

Your imagination will give you more use-cases.  

Usage
----------------------

```
/* Sample query and output */
<?php
//Build Query
$post_type_slug = 'post_order';
$selected_terms_args = array(
    'orderby' => $post_type_slug,
    'order' => 'ASC',
    'meta_query' => array(
        'relation' => 'OR',
            array(
                'key' => $post_type_slug,
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => $post_type_slug,
                'value' => 0,
                'compare' => '>='
            )
    ),
    'hide_empty' => true
);
$terms = get_terms( 'category', $selected_terms_args );

?>

<ul>
<?php
foreach( $terms as $term ) {
printf( '<li>%s</li>', $term->name );
}
?>
</ul><?php
```