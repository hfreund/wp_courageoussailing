<?php
/**
 * Variable Bundled Product template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/bundled-product-variable.php'.
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

?><div class="cart bundled_item_cart_content" data-title="<?php echo esc_attr( $bundled_item->get_raw_title() ); ?>" data-optional_suffix="<?php echo $bundled_item->is_optional() && $bundle->contains( 'mandatory' ) ? apply_filters( 'woocommerce_bundles_optional_bundled_item_suffix', __( 'optional', 'woocommerce-product-bundles' ), $bundled_item, $bundle ) : ''; ?>" data-optional="<?php echo $bundled_item->is_optional() ? 'yes' : 'no'; ?>" data-type="<?php echo $bundled_product->get_type(); ?>" data-product_variations="<?php echo htmlspecialchars( json_encode( $bundled_product_variations ) ); ?>" data-bundled_item_id="<?php echo $bundled_item->item_id; ?>" data-custom_data="<?php echo esc_attr( json_encode( $custom_product_data ) ); ?>" data-product_id="<?php echo $bundled_product_id; ?>" data-bundle_id="<?php echo $bundle_id; ?>">
	<table class="variations" cellspacing="0">
		<tbody><?php

			foreach ( $bundled_product_attributes as $attribute_name => $options ) {

				?><tr class="attribute-options" data-attribute_label="<?php echo wc_attribute_label( $attribute_name ); ?>">
					<td class="label">
						<label for="<?php echo sanitize_title( $attribute_name ) . '_' . $bundled_item->item_id; ?>"><?php echo wc_attribute_label( $attribute_name ); ?> <abbr class="required" title="<?php _e( 'Required option', 'woocommerce-product-bundles' ); ?>">*</abbr></label>
					</td>
					<td class="value"><?php

						echo wc_pb_template_bundled_variation_attribute_options( array(
							'options'      => $options,
							'attribute'    => $attribute_name,
							'bundled_item' => $bundled_item
						) );

					?></td>
				</tr><?php
			}

		?></tbody>
	</table><?php

	/**
	 * 'woocommerce_bundled_product_add_to_cart' hook.
	 *
	 * Used to output content normally hooked to 'woocommerce_before_add_to_cart_button'.
	 *
	 * @param $mixed          $bundled_product_id
	 * @param WC_Bundled_Item $bundled_item
	 */
	do_action( 'woocommerce_bundled_product_add_to_cart', $bundled_product_id, $bundled_item );

	?><div class="single_variation_wrap bundled_item_wrap"><?php

		/**
		 * 'woocommerce_bundled_single_variation' hook.
		 *
		 * Used to output variation data.
		 * @since 4.12.0
		 *
		 * @param $mixed          $bundled_product_id
		 * @param WC_Bundled_Item $bundled_item
		 *
		 * @hooked wc_bundles_single_variation - 10
		 */
		do_action( 'woocommerce_bundled_single_variation', $bundled_product_id, $bundled_item );

	?></div>
</div>
