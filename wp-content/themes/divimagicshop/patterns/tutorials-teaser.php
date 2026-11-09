<?php
/**
 * Title: Latest tutorials
 * Slug: divimagicshop/tutorials-teaser
 * Categories: divimagicshop
 * Description: The three newest tutorials, with a link to all of them.
 */
?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"margin":{"top":"var:preset|spacing|60"}}},"backgroundColor":"smoke","layout":{"type":"constrained","contentSize":"1240px"}} -->
<section class="wp-block-group alignfull has-smoke-background-color has-background" style="margin-top:var(--wp--preset--spacing--60);padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"className":"dms-shelf__head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group dms-shelf__head"><!-- wp:heading {"className":"dms-section-title"} -->
<h2 class="wp-block-heading dms-section-title">Learn a trick</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><a class="dms-more" href="/tutorials/">All tutorials</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":11,"query":{"perPage":3,"pages":0,"offset":0,"postType":"dms_tutorial","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"dms-card-grid"} -->
<div class="wp-block-query dms-card-grid"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"16rem"}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"spacing":{"margin":{"top":"0","bottom":"0.5rem"}}},"fontSize":"large"} /-->

<!-- wp:post-excerpt {"excerptLength":20,"fontSize":"small","textColor":"muted"} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></section>
<!-- /wp:group -->
