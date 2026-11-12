<?php
/**
 * The shop's dynamic blocks. Rendered in PHP; the editor shows a live preview
 * (assets/editor.js, plain JS, no build step).
 *
 *   divimagicshop/shelves           one shelf per product category (the home page)
 *   divimagicshop/shelf             one shelf for a chosen category
 *   divimagicshop/faq-list          every FAQ, grouped by topic
 *   divimagicshop/tutorial-video    the current tutorial's video
 *   divimagicshop/tutorial-products the products the current tutorial uses
 *   divimagicshop/product-tutorials tutorials that use the current product
 */

defined('ABSPATH') || exit;

add_action('init', 'dms_register_blocks');

function dms_register_blocks(): void
{
    $url = content_url('mu-plugins/divimagicshop-core/assets/');
    wp_register_script(
        'dms-blocks-editor',
        $url.'editor.js',
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-data', 'wp-core-data'],
        DMS_CORE_VERSION,
        true
    );
    wp_register_style('dms-blocks', $url.'blocks.css', [], DMS_CORE_VERSION);

    $common = ['api_version' => 3, 'category' => 'widgets', 'editor_script' => 'dms-blocks-editor', 'style' => 'dms-blocks'];
    $count = ['type' => 'integer', 'default' => 8];

    register_block_type('divimagicshop/shelves', $common + [
        'title' => 'Category shelves',
        'description' => 'One row of products for every product category, each with a "See all" link.',
        'icon' => 'columns',
        'attributes' => ['perShelf' => $count, 'exclude' => ['type' => 'string', 'default' => '']],
        'render_callback' => 'dms_render_shelves',
    ]);
    register_block_type('divimagicshop/shelf', $common + [
        'title' => 'Category shelf',
        'description' => 'One row of products from a category you choose.',
        'icon' => 'align-wide',
        'attributes' => [
            'category' => ['type' => 'string', 'default' => ''],
            'title' => ['type' => 'string', 'default' => ''],
            'perShelf' => $count,
        ],
        'render_callback' => 'dms_render_shelf_block',
    ]);
    register_block_type('divimagicshop/faq-list', $common + [
        'title' => 'FAQ list',
        'description' => 'Every FAQ question, grouped by topic. Each opens to show its answer.',
        'icon' => 'editor-help',
        'render_callback' => 'dms_render_faq_list',
    ]);
    register_block_type('divimagicshop/tutorial-video', $common + [
        'title' => 'Tutorial video',
        'icon' => 'video-alt3',
        'uses_context' => ['postId', 'postType'],
        'render_callback' => 'dms_render_tutorial_video',
    ]);
    register_block_type('divimagicshop/tutorial-products', $common + [
        'title' => 'Tutorial products',
        'description' => 'The products this tutorial uses.',
        'icon' => 'cart',
        'uses_context' => ['postId', 'postType'],
        'render_callback' => 'dms_render_tutorial_products',
    ]);
    register_block_type('divimagicshop/product-tutorials', $common + [
        'title' => 'Product tutorials',
        'description' => 'Tutorials that use this product.',
        'icon' => 'welcome-learn-more',
        'uses_context' => ['postId', 'postType'],
        'render_callback' => 'dms_render_product_tutorials',
    ]);
}

/** In the editor's preview (REST) an empty block still needs to show where it is. */
function dms_editor_placeholder(string $text): string
{
    return defined('REST_REQUEST') && REST_REQUEST
        ? '<p class="dms-placeholder">'.esc_html($text).'</p>'
        : '';
}

function dms_current_post_id(WP_Block $block): int
{
    return (int) ($block->context['postId'] ?? get_the_ID());
}

function dms_product_card(WC_Product $product): string
{
    $link = esc_url($product->get_permalink());
    $image = $product->get_image('woocommerce_thumbnail', ['class' => 'dms-card__img', 'loading' => 'lazy']);
    $badge = match (true) {
        ! $product->is_in_stock() => '<span class="dms-badge dms-badge--out">Sold out</span>',
        $product->is_on_sale() => '<span class="dms-badge">Sale</span>',
        default => '',
    };

    return '<li class="dms-card"><a class="dms-card__link" href="'.$link.'">'
        .'<span class="dms-card__media">'.$image.$badge.'</span>'
        .'<span class="dms-card__name">'.esc_html($product->get_name()).'</span>'
        .'<span class="dms-card__price">'.wp_kses_post($product->get_price_html()).'</span>'
        .'</a></li>';
}

/** @param WC_Product[] $products */
function dms_product_row(array $products): string
{
    return '<ul class="dms-shelf__row">'.implode('', array_map('dms_product_card', $products)).'</ul>';
}

function dms_render_shelf(WP_Term $term, int $limit, string $title = ''): string
{
    $products = wc_get_products([
        'status' => 'publish',
        'visibility' => 'catalog',
        'category' => [$term->slug],
        'limit' => max(1, $limit),
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    if (! $products) {
        return '';
    }

    $title = $title !== '' ? $title : $term->name;
    $link = get_term_link($term);

    return '<section class="dms-shelf">'
        .'<header class="dms-shelf__head"><h2 class="dms-shelf__title">'.esc_html($title).'</h2>'
        .(is_wp_error($link) ? '' : '<a class="dms-more" href="'.esc_url($link).'" aria-label="'.esc_attr(sprintf('See all %s', $title)).'">See all</a>')
        .'</header>'.dms_product_row($products).'</section>';
}

function dms_render_shelves(array $attributes): string
{
    if (! function_exists('wc_get_products')) {
        return dms_editor_placeholder('Category shelves need WooCommerce.');
    }

    $exclude = array_filter(array_map('trim', explode(',', (string) ($attributes['exclude'] ?? ''))));
    $uncategorized = (int) get_option('default_product_cat');
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'parent' => 0,
        'menu_order' => 'ASC', // WooCommerce's drag-and-drop category order
    ]);

    $html = '';
    foreach (is_wp_error($terms) ? [] : $terms as $term) {
        if ($term->term_id === $uncategorized || in_array($term->slug, $exclude, true)) {
            continue;
        }
        $html .= dms_render_shelf($term, (int) ($attributes['perShelf'] ?? 8));
    }

    return $html !== ''
        ? '<div class="wp-block-divimagicshop-shelves dms-shelves">'.$html.'</div>'
        : '<div class="dms-shelves dms-shelves--empty"><p>New tricks are on their way. Check back soon.</p></div>';
}

function dms_render_shelf_block(array $attributes): string
{
    $slug = (string) ($attributes['category'] ?? '');
    $term = $slug !== '' && function_exists('wc_get_products') ? get_term_by('slug', $slug, 'product_cat') : null;
    if (! $term instanceof WP_Term) {
        return dms_editor_placeholder('Choose a category for this shelf in the block settings.');
    }

    $html = dms_render_shelf($term, (int) ($attributes['perShelf'] ?? 8), (string) ($attributes['title'] ?? ''));

    return $html !== '' ? '<div class="wp-block-divimagicshop-shelf">'.$html.'</div>' : dms_editor_placeholder("No products in “{$term->name}” yet.");
}

function dms_render_faq_list(): string
{
    $faqs = get_posts([
        'post_type' => 'dms_faq',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
    ]);
    if (! $faqs) {
        return dms_editor_placeholder('No FAQ questions yet. Add them under FAQ in the admin menu.');
    }

    $topics = get_terms(['taxonomy' => 'dms_faq_topic', 'hide_empty' => true, 'orderby' => 'name']);
    $groups = [];
    foreach (is_wp_error($topics) ? [] : $topics as $topic) {
        $groups[$topic->term_id] = ['name' => $topic->name, 'items' => []];
    }
    $groups[0] = ['name' => 'More questions', 'items' => []];

    foreach ($faqs as $faq) {
        $ids = wp_get_post_terms($faq->ID, 'dms_faq_topic', ['fields' => 'ids']);
        $key = (! is_wp_error($ids) && $ids && isset($groups[$ids[0]])) ? $ids[0] : 0;
        $groups[$key]['items'][] = $faq;
    }

    $html = '<div class="wp-block-divimagicshop-faq-list dms-faq">';
    foreach ($groups as $group) {
        if (! $group['items']) {
            continue;
        }
        $html .= '<section class="dms-faq__group"><h2 class="dms-faq__topic">'.esc_html($group['name']).'</h2>';
        foreach ($group['items'] as $faq) {
            $answer = apply_filters('the_content', $faq->post_content);
            $html .= '<details class="dms-faq__item" id="faq-'.(int) $faq->ID.'">'
                .'<summary class="dms-faq__q">'.esc_html(get_the_title($faq)).'</summary>'
                .'<div class="dms-faq__a">'.$answer.'</div></details>';
        }
        $html .= '</section>';
    }

    return $html.'</div>';
}

function dms_render_tutorial_video(array $attributes, string $content, WP_Block $block): string
{
    $url = (string) get_post_meta(dms_current_post_id($block), DMS_VIDEO_META, true);
    $embed = $url !== '' ? wp_oembed_get($url) : false;
    if (! $embed) {
        return dms_editor_placeholder('The tutorial\'s video appears here (set it in "Video and products").');
    }

    return '<figure class="wp-block-divimagicshop-tutorial-video dms-video">'.$embed.'</figure>';
}

function dms_render_tutorial_products(array $attributes, string $content, WP_Block $block): string
{
    if (! function_exists('wc_get_product')) {
        return '';
    }
    $products = array_filter(array_map('wc_get_product', dms_tutorial_product_ids(dms_current_post_id($block))),
        fn ($p) => $p instanceof WC_Product && $p->get_status() === 'publish');
    if (! $products) {
        return dms_editor_placeholder('Products used in the tutorial appear here.');
    }

    return '<section class="wp-block-divimagicshop-tutorial-products dms-shelf">'
        .'<header class="dms-shelf__head"><h2 class="dms-shelf__title">What you\'ll need</h2></header>'
        .dms_product_row(array_values($products)).'</section>';
}

function dms_render_product_tutorials(array $attributes, string $content, WP_Block $block): string
{
    $productId = dms_current_post_id($block);
    $tutorials = $productId ? get_posts([
        'post_type' => 'dms_tutorial',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        'meta_query' => [['key' => DMS_PRODUCT_META, 'value' => $productId, 'type' => 'NUMERIC']],
    ]) : [];
    if (! $tutorials) {
        return dms_editor_placeholder('Tutorials that use this product appear here.');
    }

    $items = '';
    foreach ($tutorials as $tutorial) {
        $thumb = get_the_post_thumbnail($tutorial, 'medium', ['class' => 'dms-tut__img', 'loading' => 'lazy']);
        $items .= '<li class="dms-tut"><a href="'.esc_url(get_permalink($tutorial)).'">'
            .($thumb ? '<span class="dms-tut__media">'.$thumb.'</span>' : '')
            .'<span class="dms-tut__title dms-more">'.esc_html(get_the_title($tutorial)).'</span></a></li>';
    }

    return '<section class="wp-block-divimagicshop-product-tutorials dms-tuts">'
        .'<header class="dms-shelf__head"><h2 class="dms-shelf__title">Learn it</h2></header><ul class="dms-tuts__list">'.$items.'</ul></section>';
}
