<?php

// =============================================================================
// VIEWS/ETHOS/CONTENT.PHP
// -----------------------------------------------------------------------------
// Standard post output for Ethos.
// =============================================================================

$is_index_featured_layout = get_post_meta( get_the_ID(), '_x_ethos_index_featured_post_layout',  true ) == 'on' && ! is_single();

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
  <?php if ( $is_index_featured_layout ) : ?>
    <?php x_ethos_featured_index(); ?>
  <?php else : ?>
    <?php if ( has_post_thumbnail() ) : ?>
      <div class="entry-featured">
      <?php
      //
    // Author.
    //

    $author = sprintf( ' %1$s %2$s</span>',
      __( 'by', '__x__' ),
      get_the_author()
    );


    //
    // Date.
    //

    $date = sprintf( '<span><time class="entry-date" datetime="%1$s">%2$s</time></span>',
      esc_attr( get_the_date( 'c' ) ),
      esc_html( get_the_date() )
    );
    ?>
        <?php if ( ! is_single() ) : ?>
          <?php x_ethos_featured_index(); ?>
        <?php else : ?>
          <?php x_featured_image(); ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <div class="entry-wrap">
      <?php x_get_view( 'ethos', '_content', 'post-header' ); ?>
      <?php x_get_view( 'global', '_content' ); ?>
    </div>
  <?php endif; ?>
</article>