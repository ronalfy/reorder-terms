<?php
/*
Plugin Name: Reorder Terms
Plugin URI: https://wordpress.org/plugins/reorder-terms/
Description: Reorder Terms
Version: 1.0.0
Author: Ronald Huereca
Author URI: https://github.com/ronalfy/reorder-terms
Text Domain: reorder-terms
Domain Path: /languages
*/
/**
 * Reorder Post by Term
 * 
 * @package    WordPress
 * @subpackage Reorder Terms plugin
 */
final class Reorder_Terms {
	private static $instance = null;
	private $has_dependency = false;
	
	//Singleton
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	} //end get_instance
	
	/**
	 * Class constructor
	 * 
	 * Sets definitions
	 * Adds methods to appropriate hooks
	 * 
	 * @author Ronald Huereca <ronalfy@gmail.com>
	 * @since 1.0.0
	 * @access private	
	 */
	private function __construct() {
		//* Localization Code */
		load_plugin_textdomain( 'reorder-terms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		
		if ( !class_exists( 'MN_Reorder' ) || !defined( 'REORDER_ALLOW_ADDONS' ) || ( false === REORDER_ALLOW_ADDONS ) ) {
			add_action( 'admin_notices', array( $this, 'output_error_reorder_plugin' ) );//Output error	
			return;
		}
		
		require( 'class-reorder-terms-helper.php' );
		
		
		//Main init class
		add_action( 'metronet_reorder_post_types_loaded', array( $this, 'plugin_init' ) );
		
		// Initialize admin items
		add_action( 'admin_init', array( $this, 'reorder_posts_admin_init' ), 12, 1 );
		
		
	}
	
	/**
	 * Outputs error when Metronet Reorder Posts isn't installed
	 *
	 * @author Ronald Huereca <ronalfy@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @global string $pagenow  The current admin screen
	 * @uses admin_notices WordPress action
	 */
	public function output_error_reorder_plugin() {
		global $pagenow;
		if ( 'plugins.php' != $pagenow ) return;
		?>
		<div class="error">
			<p><?php printf( __( 'Reorder Terms requires <a href="%s">Reorder Posts</a> 2.1.0 or greater to be installed.', 'reorder-terms' ), 'https://wordpress.org/plugins/metronet-reorder-posts/' ); ?></p>
		</div>
		<?php	
	}
	
	/**
	 * Initializes into Reorder Posts settings section to show a term query or not
	 *
	 * @author Ronald Huereca <ronalfy@gmail.com>
	 * @since 1.1.0
	 * @access public
	 * @uses admin_init WordPress action
	 */
	public function reorder_posts_admin_init() {
		add_settings_section( 'mn-reorder-terms', _x( 'Reorder Terms', 'plugin settings heading' , 'reorder-terms' ), '__return_empty_string', 'metronet-reorder-posts' );
		
		add_settings_field( 'mn-reorder-terms-advanced', __( 'Show Terms Query', 'reorder-terms' ), array( $this, 'add_settings_field_term_query' ), 'metronet-reorder-posts', 'mn-reorder-terms', array( 'desc' => __( 'By default the terms query displays.', 'reorder-terms' ) ) );
	}
	
	/**
	 * Outputs settings section for showing a term query or not
	 *
	 * @author Ronald Huereca <ronalfy@gmail.com>
	 * @since 1.1.0
	 * @access public
	 * @uses MN_Reorder_Admin WordPress object
	 */
	public function add_settings_field_term_query() {
		$options = MN_Reorder_Admin::get_instance()->get_plugin_options();
		
		$selected = 'on';
		if ( isset( $options[ 'reorder_terms_show_query' ] ) ) {
			$selected = $options[ 'reorder_terms_show_query' ];
		}
				
		printf( '<p><input type="radio" name="metronet-reorder-posts[reorder_terms_show_query]" value="on" id="reorder_terms_show_query_yes" %s />&nbsp;<label for="reorder_terms_show_query_yes">%s</label></p>', checked( 'on', $selected, false ), esc_html__( 'Yes', 'reorder-terms' ) );
		printf( '<p><input type="radio" name="metronet-reorder-posts[reorder_terms_show_query]" value="off" id="reorder_terms_show_query_no" %s />&nbsp;<label for="reorder_terms_show_query_no">%s</label></p>', checked( 'off', $selected, false ), esc_html__( 'No', 'reorder-terms' ) );
		
	}
	
	/**
	 * Outputs error when Metronet Reorder Posts isn't installed
	 *
	 * @author Ronald Huereca <ronalfy@gmail.com>
	 * @since 1.0.0
	 * @access public
	 * @uses metronet_reorder_post_types_loaded WordPress action
	 * @param array $post_types Array of post types to initialize
	 */
	public function plugin_init( $post_types = array() ) {
			foreach( $post_types as $post_type ) {
				new Reorder_Terms_Helper( array( 'post_type' => $post_type ) );	
			}
	}
	
}
add_action( 'plugins_loaded', 'reorder_terms_instantiate' );
function reorder_terms_instantiate() {
	Reorder_Terms::get_instance();
} //end slash_edit_instantiate
