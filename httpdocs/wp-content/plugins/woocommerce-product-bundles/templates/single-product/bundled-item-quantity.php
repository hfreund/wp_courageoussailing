<?php
/**
 * Bundled Product Quantity template
 *
 * Override this template by copying it to 'yourtheme/woocommerce/single-product/bundled-item-quantity.php'.
 *
 * On occasion, this template file may need to be updated and you (the theme developer) will need to copy the new files to your theme to maintain compatibility.
 * We try to do this as little as possible, but it does happen.
 * When this occurs the version of the template file will be bumped and the readme will list any important changes.
 *
 * @version 5.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $hide_input ) {

	?><div class="quantity <?php echo 'tabular' !== $layout ? 'quantity_hidden' : ''; ?>">
		<input class="qty bundled_qty" type="hidden" name="<?php echo $input_name; ?>" value="<?php echo $quantity_min; ?>" /><?php

		if ( 'tabular' === $layout ) {
			echo $quantity_min;
		}

	?></div><?php

} else {

	ob_start();

 	woocommerce_quantity_input( array(
 		'input_name'  => $input_name,
 		'min_value'   => $quantity_min,
		'max_value'   => $quantity_max,
 		'input_value' => isset( $_REQUEST[ $input_name ] ) ? $_REQUEST[ $input_name ] : apply_filters( 'woocommerce_bundled_product_quantity', $quantity_min, $quantity_min, $quantity_max, $bundled_item )
 	), $bundled_item->product );

 	echo str_replace( 'qty text', 'qty text bundled_qty', ob_get_clean() );
}
