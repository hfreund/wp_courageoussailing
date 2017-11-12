<?php
/**
 * Composited Product Bundle template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/composited-product/bundle-product.php'.
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

?><div class="details component_data <?php echo 'layout_' . $product->get_layout(); ?>" data-price="0" data-regular_price="0" data-product_type="bundle" data-custom="<?php echo esc_attr( json_encode( $custom_data ) ); ?>"><?php

	/**
	 * 'woocommerce_composited_product_details' hook.
	 *
	 * Composited product details template.
	 *
	 * @param WC_Product_Bundle    $product
	 * @param mixed                $component_id
	 * @param WC_Product_Composite $composite_product
	 *
	 * @hooked wc_cp_composited_product_excerpt - 10
	 */
	do_action( 'woocommerce_composited_product_details', $product, $component_id, $composite_product );

	/**
	 * 'woocommerce_before_composited_bundled_items' hook.
	 *
	 * @param WC_Product_Bundle    $product
	 * @param mixed                $component_id
	 * @param WC_Product_Composite $composite_product
	 */
	do_action( 'woocommerce_before_composited_bundled_items', $product, $component_id, $composite_product );

	/**
	 * 'woocommerce_before_bundled_items' action.
	 *
	 * @param WC_Product_Bundle $product
	 */
	do_action( 'woocommerce_before_bundled_items', $product );

	foreach ( $bundled_items as $bundled_item ) {

		/**
		 * 'woocommerce_bundled_item_details' hook.
		 *
		 * @param WC_Bundled_Item   $bundled_item
		 * @param WC_Product_Bundle $bundle
		 *
		 * @hooked wc_pb_template_bundled_item_thumbnail       -   5
		 * @hooked wc_pb_template_bundled_item_details_open    -  10
		 * @hooked wc_pb_template_bundled_item_title           -  15
		 * @hooked wc_pb_template_bundled_item_description     -  20
		 * @hooked wc_pb_template_bundled_item_product_details -  25
		 * @hooked wc_pb_template_bundled_item_details_close   - 100
		 */
		do_action( 'woocommerce_bundled_item_details', $bundled_item, $product );
	}

	/**
	 * 'woocommerce_before_bundled_items' action.
	 *
	 * @param WC_Product_Bundle $product
	 */
	do_action( 'woocommerce_after_bundled_items', $product );

	/**
	 * 'woocommerce_after_composited_bundled_items' hook.
	 *
	 * @param WC_Product_Bundle    $product
	 * @param mixed                $component_id
	 * @param WC_Product_Composite $composite_product
	 */
	do_action( 'woocommerce_after_composited_bundled_items', $product, $component_id, $composite_product );

	?><div class="cart bundle_data bundle_data_<?php echo $product_id; ?>" data-bundle_price_data="<?php echo esc_attr( json_encode( $bundle_price_data ) ); ?>" data-bundle_id="<?php echo $product_id; ?>"><?php

		do_action( 'woocommerce_composited_product_add_to_cart', $product, $component_id, $composite_product );

		?><div class="bundle_wrap component_wrap">
			<div class="bundle_price"></div><?php

			$availability_html = empty( $availability[ 'availability' ] ) ? '' : '<p class="stock ' . esc_attr( $availability[ 'class' ] ) . '">' . esc_html( $availability[ 'availability' ] ) . '</p>';

			echo apply_filters( 'woocommerce_stock_html', $availability_html, $availability[ 'availability' ], $product );

			?><div class="bundle_button"><?php

				wc_get_template( 'composited-product/quantity.php', array(
					'quantity_min'      => $quantity_min,
					'quantity_max'      => $quantity_max,
					'component_id'      => $component_id,
					'product'           => $product,
					'composite_product' => $composite_product
				), '', WC_CP()->plugin_path() . '/templates/' );

			?></div>
		</div>
	</div>
</div>
