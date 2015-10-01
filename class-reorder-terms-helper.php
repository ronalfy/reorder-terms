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
		add_action( 'wp_ajax_reorder_terms_sort', array( $this, 'ajax_terms_sort' ) );
		
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
				'action' => 'reorder_term_sort',
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
        add_filter( 'get_terms_orderby', 'reorder_terms_orderby', 10, 2 );
        function reorder_terms_orderby( $sql, $args ) {
            // Do not override if being manually controlled
    		if ( ! empty( $_GET['orderby'] ) && ! empty( $_GET['taxonomy'] ) ) {
    			return $orderby;
    		}
    		$post_type = isset( $_GET[ 'type' ] ) ? sanitize_text_field( $_GET[ 'type' ] ) : '';
    		$post_type_slug = sprintf( '%s_order', $post_type );
    		    
    		// Maybe force `orderby`
    		if ( empty( $args['orderby'] ) || empty( $orderby ) || ( 
 $post_type_slug === $args['orderby'] ) || in_array( $orderby, array( 'name', 't.name' ) ) ) {
    			$orderby = 'mt1.meta_value';
    		} elseif ( 't.name' === $orderby ) {
    			$orderby = 'mt1.meta_value';
    		}
    		    		    
    		// Return possibly modified `orderby` value
    		return $orderby;
        }
        
        //Output Terms
		if ( $selected_tax ) {
    		$plugin_slug = $this->post_type . '_order';
    		$selected_terms_args = array(
        		'order' => $plugin_slug,
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
                )
    		);
    		$terms = get_terms( $selected_tax, $selected_terms_args );
    		die( '<pre>' . print_r( $terms, true ) );
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
	private function output_row( $post, $taxonomy, $term_slug ) {
		global $post;
		setup_postdata( $post );
		$menu_order = get_post_meta( $post->ID, sprintf( '_reorder_term_%s_%s', $taxonomy, $term_slug ), true );
		?>
		<li id="list_<?php the_id(); ?>" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" data-term="<?php echo esc_attr( $term_slug ); ?>" data-id="<?php the_id(); ?>" data-menu-order="<?php echo absint( $menu_order ); ?>" data-parent="0" data-post-type="<?php echo esc_attr( $post->post_type ); ?>">
			<div><?php the_title(); ?><?php echo ( defined( 'REORDER_DEBUG' ) && REORDER_DEBUG == true ) ? ' - Menu Order:' . absint( $menu_order ) : ''; ?></div>
		</li>
		<?php
	} //end output_row

	
	
	/**
	 * Helper function for outputting the posts found within the taxonomy/term
	 *
	 * @author Ronald Huereca <ronald@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @param string $post_type The current post type
	 * @param string $tax The current taxonomy
	 * @param int $term_id The term ID
	 * @uses output_interface method
	 */
	private function output_posts( $post_type, $tax, $term_id ) {
		global $mn_reorder_instances;
		
		//Get Term Meta
		$term = get_term_by( 'id', $term_id, $tax );
		if ( !$term ) {
			printf( '<div class="error"><p>%s</p></div>', esc_html__( 'Invalid Term', 'reorder-by-type' ) );
			return;
		}
		$term_slug = $term->slug;		
		
		//Build queries
		$reorder_class = isset( $mn_reorder_instances[ $post_type ] ) ? $mn_reorder_instances[ $post_type ] : false;
		$post_status = 'publish';
		$order = 'ASC';
		$main_offset = $this->offset;
		$posts_per_page = $this->posts_per_page;
		if ( $reorder_class ) {
			$post_status = $reorder_class->get_post_status();
			$order = $reorder_class->get_post_order();	
		}
		$page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 0;
		$offset = 0;
		if ( $page == 0 ) {
			$offset = 0;	
		} elseif ( $page > 1 ) {
			$offset = $main_offset * ( $page - 1 );
		}
		printf( '<input type="hidden" id="reorder-offset" value="%s" />', absint( $offset ) );
		printf( '<input type="hidden" id="reorder-tax-name" value="%s" />', esc_attr( $tax ) );
		printf( '<input type="hidden" id="reorder-term-id" value="%s" />', absint( $term_id ) );
		printf( '<input type="hidden" id="reorder-post-type" value="%s" />', esc_attr( $post_type ) );
		
		$post_query_args = array(
			'post_type' => $post_type,
			'order' => $order,
			'post_status' => $post_status,
			'posts_per_page' => 1,
			'tax_query' => array(
				array(
					'taxonomy' => $tax,
					'terms' => $term_id,
					'include_children' => false
				)	
			),
			'orderby' => 'menu_order title',
			'offset' => $offset
		);
		$tax_query_args = $post_query_args;
		unset( $tax_query_args[ 'tax_query' ] );
		$tax_query_args[ 'meta_type' ] = 'NUMERIC';
		$tax_query_args[ 'meta_key' ] = sprintf( '_reorder_term_%s_%s', $tax, $term_slug );
		$tax_query_args[ 'orderby' ] = 'meta_value_num title';
		$tax_query_args[ 'posts_per_page' ] = $posts_per_page;
		
		//Perform Queries
		add_filter( 'found_posts', array( $this, 'adjust_offset_pagination' ), 10, 2 );
		$post_query_results = new WP_Query( $post_query_args );
		$tax_query_results = new WP_Query( $tax_query_args );
		remove_filter( 'found_posts', array( $this, 'adjust_offset_pagination' ), 10, 2 );
		
		//Get post counts for both queries
		$post_query_post_count = $post_query_results->found_posts;
		$tax_query_post_count = $tax_query_results->found_posts;
		printf( '<input type="hidden" id="term-found-posts" value="%s" />', esc_attr( $tax_query_post_count ) );
		
		if ( $post_query_post_count >= 1000 ) {
			printf( '<div class="error"><p>%s</p></div>', sprintf( __( 'There are over %s posts found.  We do not recommend you sort these posts for performance reasons.', 'metronet_reorder_posts' ), number_format( $post_query_post_count ) ) );
		}
		if ( $post_query_post_count > $tax_query_post_count ) {
			//Output interface for adding custom field data to posts
			?>
			<h3><?php esc_html_e( 'Posts were found!', 'reorder-by-term' ); ?> </h3>
			<div class="updated"><p><?php esc_html_e( 'We found posts to display, however, we need to build the term data to reorder them correctly.', 'reorder-by-term' ); ?>&nbsp;<?php printf( '<a href="%s">%s</a>', esc_url( admin_url( 'tools.php?page=reorder-by-term' ) ), esc_html__( 'Build the Term Data Now.', 'reorder-by-term' ) ); ?></p></div>
			<?php //submit_button( __( 'Add data to posts', 'reorder-by-term' ), 'primary', 'reorder-add-data' ); ?>
			<?php
		} else {
			//Output Main Interface
			if( $tax_query_results->have_posts() ) {
				printf( '<h3>%s</h3>', esc_html__( 'Reorder', 'metronet-reorder-posts' ) );
				?>
				<div><img src="<?php echo esc_url( admin_url( 'images/loading.gif' ) ); ?>" id="loading-animation" /></div>
				<?php
				echo '<ul id="post-list">';
				while( $tax_query_results->have_posts() ) {
					global $post;
					$tax_query_results->the_post();
					$this->output_row( $post, $tax, $term_slug );	
				}
				echo '</ul><!-- #post-list -->';
				
				//Show pagination links
				if( $tax_query_results->max_num_pages > 1 ) {
					echo '<div id="reorder-pagination">';
					$current_url = add_query_arg( array( 'paged' => '%#%' ) );
					$pagination_args = array(
						'base' => $current_url,
						'total' => $tax_query_results->max_num_pages,
						'current' => ( $page == 0 ) ? 1 : $page
					);
					echo paginate_links( $pagination_args );
					echo '</div>';
				}
				printf( '<h3>%s</h3>', esc_html__( 'Reorder Terms Query', 'reorder-by-term' ) );
				printf( '<p>%s</p>', esc_html__( 'You will need custom code to query by term.  Here are some example query arguments.', 'reorder-by-term' ) );
				$meta_key = sprintf( '_reorder_term_%s_%s', $tax, $term_slug );
$query = "
'post_type' => '{$post_type}',
'order' => '{$order}',
'post_status' => '{$post_status}',
'posts_per_page' => 50,
'meta_key' => '{$meta_key }',
'orderby' => 'meta_value_num title'
";
				printf( '<blockquote><pre><code>%s</code></pre></blockquote>', esc_html( print_r( $query, true ) ) );
			} else {
				echo sprintf( '<h3>%s</h3>	', esc_html__( 'There is nothing to sort at this time', 'metronet-reorder-posts' ) );	
			}	
		}
	} //end output_posts
	
	
}	