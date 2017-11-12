<?php

// =============================================================================
// VIEWS/ETHOS/_CONTENT-POST-HEADER.PHP
// -----------------------------------------------------------------------------
// Standard <header> output for various posts.
// =============================================================================

?>

<header>
  <?php if ( is_single() ) : ?>
    <?php x_entry_navigation(); ?>
    <h1 class="entry-title"><?php the_title(); ?></h1>
  <?php else : ?>
      <a href="<?php the_permalink(); ?>" class="x-brand text" title="<?php echo esc_attr( sprintf( __( '"%s"', '__x__' ), the_title_attribute( 'echo=0' ) ) ); ?>"><?php x_the_alternate_title(); ?></a>
    </h2>
  <?php endif; ?>
  <?php x_ethos_entry_meta(); ?>
</header>