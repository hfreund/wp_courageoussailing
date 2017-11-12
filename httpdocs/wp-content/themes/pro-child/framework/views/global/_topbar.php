<?php

// =============================================================================
// VIEWS/GLOBAL/_TOPBAR.PHP
// -----------------------------------------------------------------------------
// Includes topbar output.
// =============================================================================

?>

<?php if ( x_get_option( 'x_topbar_display', '0' ) == '1' ) : ?>

  <div class="x-topbar">
    <!-- <div class="x-topbar-inner x-container">
      <?php // if ( x_get_option( 'x_topbar_content' ) != '' ) : ?>
      <p class="p-info">
        <?php // echo x_get_option( 'x_topbar_content' ); ?>
      </p>
      <?php // endif; ?>
      <?php // x_social_global(); ?>
    </div> -->
     <div class="top-nav">
		  <div class="x-container">
     		<?php ubermenu( 'main' , array( 'menu' => 182 ) ); ?>
     	</div>
	  </div>
  </div>

<?php endif; ?>