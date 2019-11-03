<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Background_Process', false ) ) {
  include_once dirname( WC_PLUGIN_FILE ) . '/abstracts/class-wc-background-process.php';
}

class VP_WC_Increase_Price_In_Bulk_BG extends WC_Background_Process {

	public function __construct() {
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'vp_wc_increase_price_in_bulk';
		parent::__construct();
	}

	protected function task( $item ) {
    if ( ! $item || empty( $item['task'] ) ) {
			return false;
		}

		$process_count = 0;
		$process_limit = 100;

		switch ( $item['task'] ) {
			case 'update_products':
				$process_count = $this->update_products( $process_limit );
				break;
		}

		if ( $process_limit === $process_count ) {
			// Needs to run again.
			return $item;
		} else {
      update_option('_vp_wc_increase_price_in_bulk_running', false);
      update_option('_vp_wc_increase_price_in_bulk_finished', true);
    }

		return false;
	}

  public function update_products($limit = 20) {
    $query = array(
      'limit' => $limit,
			'meta_key'     => '_vp_wc_price_updated',
			'meta_compare' => 'NOT EXISTS'
    );

    $products = wc_get_products( $query );
		$count = 0;

    foreach ($products as $product) {
      if( $product->is_type('variable') ){
        foreach( $product->get_available_variations() as $variation_values ){
          $variation_id = $variation_values['variation_id']; // variation id
          $price = $variation_values['display_regular_price']*(1+5/100);
          update_post_meta( $variation_id, '_regular_price', $price );
          update_post_meta( $variation_id, '_price', $price );
          wc_delete_product_transients( $variation_id );
        }
        wc_delete_product_transients( $product->get_id() );

      } else {
        $price = $product->get_price()*(1+5/100);
        $product->set_regular_price( $price );
        $product->set_price( $price );
      }

      $product->add_meta_data('_vp_wc_price_updated', true);
      $product->save();

      $count++;
    }
		return $count;
  }

}
