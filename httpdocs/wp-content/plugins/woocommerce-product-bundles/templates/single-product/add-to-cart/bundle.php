<?php
/**
 * Product Bundle single-product template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/add-to-cart/bundle.php'.
 *
 * On occasion, this template file may need to be updated and you (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the readme will list any important changes.
 *
 * @version 5.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form method="post" enctype="multipart/form-data" class="cart cart_group bundle_form <?php echo 'layout_' . $product->get_layout(); ?>"><?php

	/**
	 * 'woocommerce_before_bundled_items' action.
	 *
	 * @param WC_Product_Bundle $product
	 */
	do_action( 'woocommerce_before_bundled_items', $product );

	foreach ( $bundled_items as $bundled_item ) {

		/**
		 * 'woocommerce_bundled_item_details' hook
		 *
		 * @hooked wc_pb_template_bundled_item_details_wrapper_open  -   0
		 * @hooked wc_pb_template_bundled_item_thumbnail             -   5
		 * @hooked wc_pb_template_bundled_item_details_open          -  10
		 * @hooked wc_pb_template_bundled_item_title                 -  15
		 * @hooked wc_pb_template_bundled_item_description           -  20
		 * @hooked wc_pb_template_bundled_item_product_details       -  25
		 * @hooked wc_pb_template_bundled_item_details_close         -  30
		 * @hooked wc_pb_template_bundled_item_details_wrapper_close - 100
		 */
		do_action( 'woocommerce_bundled_item_details', $bundled_item, $product );
	}

	/**
	 * 'woocommerce_after_bundled_items' action.
	 *
	 * @param WC_Product_Bundle $product
	 */
	do_action( 'woocommerce_after_bundled_items', $product ); ?>

	<div class="cart bundle_data bundle_data_<?php echo $product_id; ?>" data-bundle_price_data="<?php echo esc_attr( json_encode( $bundle_price_data ) ); ?>" data-bundle_id="<?php echo $product_id; ?>"><?php

		if ( $product->is_purchasable() ) {

			/**
			 * 'woocommerce_before_add_to_cart_button' action.
			 */
			do_action( 'woocommerce_before_add_to_cart_button' );

			?><div class="bundle_wrap">
				<div class="bundle_price"></div>
				<div class="bundle_error" style="display:none"><ul class="msg woocommerce-info"></ul></div><?php

				$availability      = $product->get_availability();
				$availability_html = empty( $availability[ 'availability' ] ) ? '' : '<p class="stock ' . esc_attr( $availability[ 'class' ] ) . '">' . esc_html( $availability[ 'availability' ] ) . '</p>';

				echo apply_filters( 'woocommerce_stock_html', $availability_html, $availability[ 'availability' ], $product );

				?><div class="bundle_button"><?php

					/**
					 * woocommerce_bundles_add_to_cart_button hook.
					 *
					 * @hooked wc_pb_template_add_to_cart_button - 10
					 */
					do_action( 'woocommerce_bundles_add_to_cart_button' );

				?></div>
				<input type="hidden" name="add-to-cart" value="<?php echo $product_id; ?>" />
			</div><?php

			/** WC Core action. */
			do_action( 'woocommerce_after_add_to_cart_button' );

		} else {

			?><div class="bundle_unavailable woocommerce-info"><?php
				echo __( 'This product is currently unavailable.', 'woocommerce-product-bundles' );
			?></div><?php
		}

	?></div><?php

?></form><?php
	/** WC Core action. */
	do_action( 'woocommerce_after_add_to_cart_form' );
?>
