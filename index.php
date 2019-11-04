<?php
/*
Plugin Name: VP WooCommerce Áremelés
Plugin URI: http://visztpeter.me
Description: Termék árának emelése X százalékkal
Author: Viszt Péter
Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class VP_WC_Increase_Price_In_Bulk {
  protected static $_instance = null;
  protected static $background_generator;

  //Get main instance
  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  //Construct
  public function __construct() {

    //Plugin loaded
    add_action( 'plugins_loaded', array( $this, 'init' ) );

  }

  public function init() {
    add_action('admin_menu', array( $this, 'create_menu' ));

    //Setup utility
    require_once( plugin_dir_path( __FILE__ ) . 'class-bg-process.php' );
    self::$background_generator = new VP_WC_Increase_Price_In_Bulk_BG();
  }

  //Create submenu in Tools
  public function create_menu() {
    $hook = add_submenu_page( 'woocommerce', 'Increase prices', 'Increase prices', 'manage_options', 'vp-wc-increase-price-in-bulk', array( $this, 'generate_page_content' ) );
    add_action( "load-$hook", array( $this, 'process_page_submit' ) );
  }

  function generate_page_content() {
    ?>
    <div class="wrap">

      <?php if(get_option('_vp_wc_increase_price_in_bulk_running')) :?>
      <div class="notice notice-success is-dismissible">
        <p>Increasing prices started... it might take a while. It will raise prices by <?php echo get_option('_vp_wc_increase_price_in_bulk_percentage'); ?>%.</p>
      </div>
      <?php endif; ?>

      <?php if(get_option('_vp_wc_increase_price_in_bulk_finished')) :?>
      <div class="notice notice-success is-dismissible">
        <p>Increasing prices finished!</p>
      </div>
      <?php endif; ?>

      <h1>Increase prices by X percentage</h1>
      <form method="post">
        <label>Percentage</label><br>
        <input type="text" name="percentage" value="5"><br>
        <?php submit_button( 'Increase prices', 'primary', 'vp_wc_increase_price_in_bulk_save' ); ?>
        <p>Make sure you have a backup first!</p>
      </form>
    </div>
    <?php
  }

  public function process_page_submit() {
    if ( ! empty( $_POST['vp_wc_increase_price_in_bulk_save'] ) ) {
      update_option('_vp_wc_increase_price_in_bulk_session', time() );
      update_option('_vp_wc_increase_price_in_bulk_percentage', intval($_POST['percentage']) );
      update_option('_vp_wc_increase_price_in_bulk_finished', false);
      update_option('_vp_wc_increase_price_in_bulk_running', true);

      self::$background_generator->push_to_queue( array( 'task' => 'update_products' ) );
      self::$background_generator->save()->dispatch();
    }
  }

}

//Initialize
VP_WC_Increase_Price_In_Bulk::instance();
