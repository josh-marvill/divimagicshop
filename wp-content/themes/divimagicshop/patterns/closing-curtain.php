<?php
/**
 * Title: Closing curtain
 * Slug: divimagicshop/closing-curtain
 * Categories: divimagicshop, call-to-action
 * Description: Black band with a red top edge before the footer: a closing line, a shop button and a product search.
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"dms-closing","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"},"margin":{"top":"0"}},"border":{"top":{"color":"var:preset|color|red","width":"4px"}}},"backgroundColor":"ink","textColor":"paper","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull dms-closing has-paper-color has-ink-background-color has-text-color has-background" style="border-top-color:var(--wp--preset--color--red);border-top-width:4px;margin-top:0;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:columns {"verticalAlignment":"bottom","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60","top":"var:preset|spacing|50"}}}} -->
<div class="wp-block-columns are-vertically-aligned-bottom"><!-- wp:column {"verticalAlignment":"bottom","width":"55%"} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:55%"><!-- wp:heading {"className":"dms-closing__title"} -->
<h2 class="wp-block-heading dms-closing__title">Your next trick is <em>in the shop.</em></h2>
<!-- /wp:heading -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/">Browse the shop</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"bottom","width":"45%"} -->
<div class="wp-block-column is-vertically-aligned-bottom" style="flex-basis:45%"><!-- wp:paragraph {"className":"dms-closing__hint","textColor":"muted-on-ink"} -->
<p class="dms-closing__hint has-muted-on-ink-color has-text-color">Know what you're after?</p>
<!-- /wp:paragraph -->

<!-- wp:divimagicshop/product-search {"placeholder":"Search tricks, cards, coins…"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></section>
<!-- /wp:group -->
