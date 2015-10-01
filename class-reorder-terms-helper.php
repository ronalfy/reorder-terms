<?php
/**
 * Reorder Terms Helper Class
 * 
 * @package    WordPress
 * @subpackage Reorder by Term plugin
 */
final class Reorder_Terms_Helper  {
	private $post_type;
	private $posts_per_page;
	private $offset;
	private $reorder_page;
	private $tab_url;
	
	/**
	 * Class constructor
	 * 
	 * Sets definitions
	 * Adds methods to appropriate hooks
	 * 
	 * @author Ronald Huereca <ronalfy@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @param array $args    If not set, then uses $defaults instead
	 */
	public function __construct( $args ) {
		// Parse arguments
		$defaults = array(
			'post_type'   => '',
			'posts_per_page' => 50,
			'offset' => 48
		);
		$args = wp_parse_args( $args, $defaults );

		// Set variables
		$this->post_type   = $args[ 'post_type' ];
		
		//Get offset and posts_per_page
		$this->posts_per_page = absint( $args[ 'posts_per_page' ] ); //todo - filterable?
		$this->offset = absint( $args[ 'offset' ] ); //todo - filterable?
		if ( $this->offset > $this->posts_per_page ) {
			$this->offset = $this->posts_per_page;	
		}
		
		//Add-on actions/filters
		add_action( 'metronet_reorder_menu_url_' . $this->post_type, array( $this, 'set_reorder_url' ) );
		add_action( 'reorder_by_terms_interface_' . $this->post_type, array( $this, 'output_interface' ) );
		add_action( 'metronet_reorder_posts_add_menu_' . $this->post_type, array( $this, 'script_init' ) );
		add_filter( 'metronet_reorder_posts_tabs_' . $this->post_type, array( $this, 'add_tab' ) );
	
		//Ajax actions
		add_action( 'wp_ajax_reorder_terms_only_sort', array( $this, 'ajax_term_sort' ) );
		
		add_filter( 'get_terms_orderby', array( $this, 'reorder_terms_orderby' ), 10, 2 );
		
	}
	/**
	 * Saving the post oder for later use
	 *
	 * @author Ronald Huereca <ronalfy@gmail.com>
	 * @since Reorder 1.0
	 * @access public
	 * @global object $wpdb  The primary global database object used internally by WordPress
	 */
	public function ajax_term_sort() {
		global $wpdb;
		
		if ( !current_user_can( 'edit_pages' ) ) die( '' );
		
		// Verify nonce value, for security purposes
		if ( !wp_verify_nonce( $_POST['nonce'], 'sortnonce' ) ) die( '' );

		//Get Ajax Vars
		$post_parent = 0;
		$menu_order_start = isset( $_POST[ 'start' ] ) ? absint( $_POST[ 'start' ] ) : 0;
		$post_id = isset( $_POST[ 'post_id' ] ) ? absint( $_POST[ 'post_id' ] ) : 0;
		$post_menu_order = isset( $_POST[ 'menu_order' ] ) ? absint( $_POST[ 'menu_order' ] ) : 0;
		$posts_to_exclude = isset( $_POST[ 'excluded' ] ) ? array_filter( $_POST[ 'excluded' ], 'absint' ) : array();
		$post_type = isset( $_POST[ 'post_type' ] ) ? sanitize_text_field( $_POST[ 'post_type' ] ) : false;
		$attributes = isset( $_POST[ 'attributes' ] ) ? $_POST[ 'attributes' ] : array();
		
		$taxonomy = $term_slug = false;
		//Get the tax and term slug
		foreach( $attributes as $attribute_name => $attribute_value ) {
			if ( 'data-taxonomy' == $attribute_name ) {
				$taxonomy = sanitize_text_field( $attribute_value );
			} elseif ( 'data-term' == $attribute_name ) {
				$term_slug = sanitize_text_field( $attribute_value );	
			}
		}
		
		if ( !$post_type || !$taxonomy || !$term_slug  ) die( '' );
		
		//Build Initial Return 
		$return = array();
		$return[ 'more_posts' ] = false;
		$return[ 'action' ] = 'reorder_term_sort';
		$return[ 'post_parent' ] = $post_parent;
		$return[ 'nonce' ] = sanitize_text_field( $_POST[ 'nonce' ] );
		$return[ 'post_id'] = $post_id;
		$return[ 'menu_order' ] = $post_menu_order;
		$return[ 'post_type' ] = $post_type;
		$return[ 'attributes' ] = $attributes;
		
		//Update post if passed - Should run only on beginning of first iteration
		if( $post_id > 0 && !isset( $_POST[ 'more_posts' ] ) ) {
			update_term_meta( $post_id, $term_slug, $post_menu_order );
			$posts_to_exclude[] = $post_id;
		}
		
		//Build query
		$reorder_class = isset( $mn_reorder_instances[ $post_type ] ) ? $mn_reorder_instances[ $post_type ] : false;
		$post_status = 'publish';
		$order = 'ASC';
		if ( $reorder_class ) {
			$post_status = $reorder_class->get_post_status();
			$order = $reorder_class->get_post_order();	
		}
		
		$post_type_slug = $post_type . '_order';
		
		//Build Query
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
            'parent' => $post_parent
        );
		$terms = get_terms( $taxonomy, $posts );
		$start = $menu_order_start;
		if ( $terms ) {
			foreach( $terms as $term ) {
				//Increment start if matches menu_order and there is a post to change
				if ( $start == $post_menu_order && $post_id > 0 ) {
					$start++;	
				}
				
				if ( $post_id != $term->term_id ) {
					//Update post and counts
					update_term_meta( $term->term_id, $this->post_type . '_order', $start );	
				}
				$posts_to_exclude[] = $term->term_id;
				$start++;
			}
			$return[ 'excluded' ] = $posts_to_exclude;
			$return[ 'start' ] = $start;
			if ( count( $terms ) > 1 ) {
				$return[ 'more_posts' ] = true;	
			} else {
				$return[ 'more_posts' ] = false;	
			}
			die( json_encode( $return ) );
		} else {
			die( json_encode( $return ) );
		}
	}	
	
	/**
	 * Adjust the found posts for the offset
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @returns string $found_posts Number of posts
	 * @uses found_posts WordPress filter
	 */
	public function adjust_offset_pagination( $found_posts, $query ) {
		//This sometimes will have a bug of showing an extra page, but it doesn't break anything, so leaving it for now.
		if( $found_posts > $this->posts_per_page ) {
			$num_pages = $found_posts / $this->offset;
			$found_posts = (string)round( $num_pages * $this->posts_per_page );
		}
		return $found_posts;
	}
	
	/**
	 * Print out our scripts
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since 1.0.0
	 * @access public
	 */
	public function print_scripts() {
		//Overwrite action variable by de-registering sort script and adding it back in
		if ( isset( $_GET[ 'tab' ] ) && 'reorder-terms' == $_GET[ 'tab' ] ) {
			//Main Reorder Script
			wp_deregister_script( 'reorder_posts' );
			wp_enqueue_script( 'reorder_posts', REORDER_URL . '/scripts/sort.js', array( 'reorder_nested' ) ); //CONSTANT REORDER_URL defined in Metronet Reorder Posts
			wp_localize_script( 'reorder_posts', 'reorder_posts', array(
				'action' => 'reorder_terms_only_sort',
				'expand' => esc_js( __( 'Expand', 'metronet-reorder-posts' ) ),
				'collapse' => esc_js( __( 'Collapse', 'metronet-reorder-posts' ) ),
				'sortnonce' =>  wp_create_nonce( 'sortnonce' ),
				'hierarchical' => false,
			) );	
			
			//Main Term Script
			wp_enqueue_script( 'reorder_terms', plugins_url( '/js/main.js', __FILE__ ), array( 'reorder_posts' ) );
			wp_localize_script( 'reorder_terms', 'reorder_terms', array(
				'action' => 'term_build',
				'loading_text' => __( 'Loading...  Do not Refresh', 'reorder-by-term' ),
				'refreshing_text' => __( 'Refreshing...', 'reorder-by-term' ),
				'sortnonce' =>  wp_create_nonce( 'reorder-term-build' ),
			) );
			
		}
	}
	
	/**
	 * Sets the menu location URL for Reorder Posts
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @param string $url The menu location URL
	 * @uses metronet_reorder_menu_url_{post_type} WordPress action
	 */
	public function set_reorder_url( $url ) {
		$this->reorder_page = $url;

		
	}
	
	/**
	 * Add our own scripts to the Reorder menu item
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @param string $menu_hook Menu hook to latch onto
	 * @uses metronet_reorder_posts_add_menu_{post_type} WordPress filter
	 */
	public function script_init( $menu_hook ) {
		add_action( 'admin_print_scripts-' . $menu_hook, array( $this, 'print_scripts' ), 20 );
	}
	
	/**
	 * Add a custom tab to the Reorder screen
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @param array $tabs Current tabs
	 * @return array $tabs Updated tabs
	 * @uses metronet_reorder_posts_tabs_{post_type} WordPress filter
	 */
	public function add_tab( $tabs = array() ) {
		//Make sure there are taxonomies attached to this post
		$taxonomies = get_object_taxonomies( $this->post_type );
		if ( empty( $taxonomies ) ) return $tabs;
		
		$this->tab_url = add_query_arg( array( 'tab' => 'reorder-terms', 'type' => $this->post_type ), $this->reorder_page );	
		
		//Return Tab
		$tabs[] = array(
			'url' => $this->tab_url,
			'label' => __( 'Reorder Terms', 'reorder-by-term' ),
			'get' => 'reorder-terms' /*$_GET variable*/,
			'action' => 'reorder_by_terms_interface_' . $this->post_type
		);
		return $tabs;
	}
	
	function reorder_terms_orderby( $sql, $args ) {
    	if ( isset( $args[ 'orderby' ] ) ) {
        	if ( strstr( $args[ 'orderby' ], 'order' ) ) {
        	    $orderby = 'mt1.meta_value';
                return $ordserby;
            }
        	
    	}
        return $sql;
    }
	
	/**
	 * Output the main HTML interface of taxonomy/terms/posts
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @uses reorder_by_term_interface_{post_type} WordPress action
	 */
	public function output_interface() {
        $selected_tax = isset( $_GET[ 'taxonomy' ] ) ? $_GET[ 'taxonomy' ] : false;
		$taxonomies = get_object_taxonomies( $this->post_type, 'objects' );
		?>
		<h3><?php esc_html_e( 'Select a Taxonomy', 'reorder-by-term' ); ?></h3>
		<form id="reorder-taxonomy" method="get" action="<?php echo esc_url( $this->reorder_page ); ?>">
		<?php 
		foreach( $_GET as $key => $value ) {
				if ( 'term' == $key || 'taxonomy' == $key || 'paged' == $key ) continue;
				printf( '<input type="hidden" value="%s" name="%s" />', esc_attr( $value ), esc_attr( $key ) );
		}
		//Output non hierarchical posts
		$page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 0;
		if ( $page == 0 ) {
			$offset = 0;	
		} elseif ( $page > 1 ) {
			$offset = $this->offset * ( $page - 1 );
		}
		printf( '<input type="hidden" id="reorder-offset" value="%s" />', absint( $offset ) );
		?>
		<select name="taxonomy">
			<?php
			printf( '<option value="none">%s</option>', esc_html__( 'Select a taxonomy', 'reorder-by-term' ) );
			foreach( $taxonomies as  $tax_name => $taxonomy ) {
				$label = $taxonomy->label;
				printf( '<option value="%s" %s>%s</option>', esc_attr( $tax_name ), selected( $tax_name, $selected_tax, false ),  esc_html( $label ) );
			}				
			?>
		</select>
		</form>
        <?php
        
        
        
        //Output Terms
		if ( $selected_tax ) {
    		$plugin_slug = $this->post_type . '_order';
    		$selected_terms_args = array(
        		'orderby' => $plugin_slug,
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => $plugin_slug,
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => $plugin_slug,
                        'value' => 0,
                        'compare' => '>='
                    )
                ),
                'number' => 50,
                'offset' => 0,
                'parent' => 0
    		);
    		$terms = get_terms( $selected_tax, $selected_terms_args );
    		    		
    		if ( $terms ) {
        		echo '<ul id="post-list">';
    			foreach( $terms as $term ) {
    				$this->output_row( $term );	
    			}
    			echo '</ul><!-- #post-list -->';
    		}
        }
    
    }
       
	/**
	 * Outputs a post to the screen
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since 1.0.0
	 * @access private
	 * @param object $post The WordPress Post object
	 * @param string $taxonomy The current taxonomy
	 * @param string $term_slug The term Slug
	 * @uses output_posts method
	 */
	private function output_row( $term ) {
		$term_order = get_term_meta( $term->term_id, $this->post_type . '_order', true );
		if ( !$term_order ) {
    		$term_order = 0;
		}
		$taxonomy = $term->taxonomy;
		?>
		<li id="list_<?php echo esc_attr( $term->term_id ) ?>" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" data-term="<?php echo esc_attr( $term->slug ); ?>" data-id="<?php echo esc_attr( $term->term_id ); ?>" data-menu-order="0" data-parent="<?php echo esc_attr( $term->parent ); ?>" data-post-type="<?php echo esc_attr( $this->post_type ); ?>">
			<div><?php echo esc_html( $term->name ); ?><?php echo ( defined( 'REORDER_DEBUG' ) && REORDER_DEBUG == true ) ? ' - Menu Order:' . absint( $term_order ) : ''; ?></div>
			<?php
        		$plugin_slug = $this->post_type . '_order';
        		$selected_terms_args = array(
            		'orderby' => $plugin_slug,
                    'order' => 'ASC',
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => $plugin_slug,
                            'compare' => 'NOT EXISTS'
                        ),
                        array(
                            'key' => $plugin_slug,
                            'value' => 0,
                            'compare' => '>='
                        )
                    ),
                    'parent' => $term->term_id
                );
                $child_terms = get_terms( $taxonomy = $term->taxonomy, $selected_terms_args );
                if ( $child_terms ) {
                    echo '<ul class="has-term-children">';
                    foreach( $child_terms as $child_term ) {
                        $this->output_row( $child_term );
                    }
                    echo '</ul>';
                }
                
    		?>	
		</li>
		<?php
	} //end output_row
	
	
}	