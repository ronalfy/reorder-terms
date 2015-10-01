<?php
/*
Plugin Name: Reorder Terms
Plugin URI: https://wordpress.org/plugins/reorderterms/
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
 * @subpackage Reorder by Term plugin
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
			<p><?php printf( __( 'Reorder By Term requires <a href="%s">Reorder Posts</a> 2.1.0 or greater to be installed.', 'reorder-by-term' ), 'https://wordpress.org/plugins/metronet-reorder-posts/' ); ?></p>
		</div>
		<?php	
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