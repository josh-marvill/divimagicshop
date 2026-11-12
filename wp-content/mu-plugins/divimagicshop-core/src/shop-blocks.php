<?php
/**
 * Blocks that make the store browse like a shop: search, category navigation, filters,
 * a spotlight and product grids. Rendered in PHP, previewed live in the editor.
 *
 *   divimagicshop/product-search   a search box that only searches products
 *   divimagicshop/category-bar     a row of links to every top-level category (header)
 *   divimagicshop/category-tiles   picture tiles, one per top-level category (home)
 *   divimagicshop/spotlight        one featured (or newest) product, large (home welcome)
 *   divimagicshop/product-grid     newest, on-sale or featured products (home)
 *   divimagicshop/shop-filters     categories, price and stock filters (shop sidebar)
 *   divimagicshop/active-filters   the filters in use, each removable (above the grid)
 *
 * Filters are plain links. The shop grid reads WooCommerce's own min_price / max_price
 * from the URL; in_stock=1 is added below.
 */

defined('ABSPATH') || exit;

add_action('init', 'dms_register_shop_blocks');

function dms_register_shop_blocks(): void
{
    $common = ['api_version' => 3, 'category' => 'widgets', 'editor_script' => 'dms-blocks-editor', 'style' => 'dms-blocks'];

    register_block_type('divimagicshop/product-search', $common + [
        'title' => 'Product search',
        'description' => 'A search box that finds products.',
        'icon' => 'search',
        'attributes' => ['placeholder' => ['type' => 'string', 'default' => 'Search tricks']],
        'render_callback' => 'dms_render_product_search',
    ]);
    register_block_type('divimagicshop/category-bar', $common + [
        'title' => 'Category bar',
        'description' => 'A row of links to every top-level product category.',
        'icon' => 'menu',
        'render_callback' => 'dms_render_category_bar',
    ]);
    register_block_type('divimagicshop/category-tiles', $common + [
        'title' => 'Category tiles',
        'description' => 'Picture tiles for the first top-level categories, in WooCommerce\'s category order (its image, or its newest product\'s).',
        'icon' => 'grid-view',
        'attributes' => ['count' => ['type' => 'integer', 'default' => 8]],
        'render_callback' => 'dms_render_category_tiles',
    ]);
    register_block_type('divimagicshop/spotlight', $common + [
        'title' => 'Product spotlight',
        'description' => 'One product, large: the first featured product (star it in Products), otherwise the newest.',
        'icon' => 'star-filled',
        'render_callback' => 'dms_render_spotlight',
    ]);
    register_block_type('divimagicshop/product-grid', $common + [
        'title' => 'Product grid',
        'description' => 'Newest, on-sale or featured products. Shows nothing when there are none.',
        'icon' => 'screenoptions',
        'attributes' => [
            'source' => ['type' => 'string', 'default' => 'new'],
            'title' => ['type' => 'string', 'default' => 'Just in'],
            'count' => ['type' => 'integer', 'default' => 8],
        ],
        'render_callback' => 'dms_render_product_grid',
    ]);
    register_block_type('divimagicshop/shop-filters', $common + [
        'title' => 'Shop filters',
        'description' => 'Categories, price ranges and an in-stock filter for the shop pages.',
        'icon' => 'filter',
        'render_callback' => 'dms_render_shop_filters',
    ]);
    register_block_type('divimagicshop/active-filters', $common + [
        'title' => 'Active filters',
        'description' => 'The search and filters in use, each with a remove link.',
        'icon' => 'dismiss',
        'render_callback' => 'dms_render_active_filters',
    ]);
}

// "In stock only" on the shop, category and product search pages.
add_action('pre_get_posts', function (WP_Query $q): void {
    if (is_admin() || ! $q->is_main_query() || empty($_GET['in_stock'])) { // phpcs:ignore WordPress.Security.NonceVerification
        return;
    }
    $isProducts = $q->is_post_type_archive('product') || $q->is_tax(get_object_taxonomies('product'))
        || ($q->is_search() && $q->get('post_type') === 'product');
    if (! $isProducts) {
        return;
    }
    $meta = (array) $q->get('meta_query');
    $meta[] = ['key' => '_stock_status', 'value' => ['instock', 'onbackorder'], 'compare' => 'IN'];
    $q->set('meta_query', $meta);
});

/* ---------------------------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------------------------ */

/** @return WP_Term[] Top-level categories with products, in WooCommerce's order, minus "Uncategorized". */
function dms_top_categories(): array
{
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'parent' => 0,
        'menu_order' => 'ASC',
    ]);
    $uncategorized = (int) get_option('default_product_cat');

    return array_values(array_filter(is_wp_error($terms) ? [] : $terms, fn (WP_Term $t) => $t->term_id !== $uncategorized));
}

function dms_current_category(): ?WP_Term
{
    $term = is_product_category() ? get_queried_object() : null;

    return $term instanceof WP_Term ? $term : null;
}

/** The current filters, from the URL. */
function dms_active_filters(): array
{
    // phpcs:disable WordPress.Security.NonceVerification
    $min = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? max(0, (float) $_GET['min_price']) : null;
    $max = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? max(0, (float) $_GET['max_price']) : null;

    return [
        'min_price' => $min,
        'max_price' => $max,
        'in_stock' => ! empty($_GET['in_stock']),
        'orderby' => isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '',
        's' => is_search() ? get_search_query(false) : '',
    ];
    // phpcs:enable
}

/**
 * The URL of the page being browsed (shop, category or product search), first page, with
 * the current filters, then $changes applied (null removes a filter). $wholeShop drops the
 * category and the search and keeps the filters.
 */
function dms_filter_url(array $changes = [], bool $wholeShop = false): string
{
    $term = $wholeShop ? null : dms_current_category();
    $f = dms_active_filters();
    if ($wholeShop) {
        $f['s'] = '';
    }
    $args = array_filter([
        'min_price' => $f['min_price'],
        'max_price' => $f['max_price'],
        'in_stock' => $f['in_stock'] ? 1 : null,
        'orderby' => $f['orderby'] ?: null,
    ], fn ($v) => $v !== null);

    if ($term) {
        $base = get_term_link($term);
        $base = is_wp_error($base) ? wc_get_page_permalink('shop') : $base;
    } elseif ($f['s'] !== '') {
        $base = home_url('/');
        $args = ['s' => $f['s'], 'post_type' => 'product'] + $args;
    } else {
        $base = wc_get_page_permalink('shop');
    }

    foreach ($changes as $key => $value) {
        if ($value === null) {
            unset($args[$key]);
        } else {
            $args[$key] = $value;
        }
    }

    return add_query_arg(array_map('rawurlencode', array_map('strval', $args)), $base);
}

/** Lowest and highest price among published products, optionally within categories. */
function dms_price_span(array $termIds = []): ?array
{
    global $wpdb;
    $join = '';
    $where = '';
    if ($termIds) {
        $ids = implode(',', array_map('intval', $termIds));
        $join = "INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = l.product_id
                 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_cat'";
        $where = "AND tt.term_id IN ($ids)";
    }
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- integers only
    $row = $wpdb->get_row("SELECT MIN(l.min_price) lo, MAX(l.max_price) hi FROM {$wpdb->wc_product_meta_lookup} l
        INNER JOIN {$wpdb->posts} p ON p.ID = l.product_id AND p.post_status = 'publish' AND p.post_type = 'product'
        $join WHERE l.max_price > 0 $where");

    return $row && $row->hi !== null ? ['min' => (float) $row->lo, 'max' => (float) $row->hi] : null;
}

/** A round step (1, 2, 2.5 or 5 × 10^n) that splits the span into about four bands. */
function dms_price_step(float $max): float
{
    $raw = $max / 4;
    if ($raw <= 0) {
        return 0;
    }
    $mag = 10 ** floor(log10($raw));
    foreach ([1, 2, 2.5, 5, 10] as $m) {
        if ($raw <= $m * $mag) {
            return $m * $mag;
        }
    }

    return 10 * $mag;
}

/** @return array<int, array{label:string, min:?float, max:?float}> */
function dms_price_bands(?WP_Term $term): array
{
    $ids = [];
    if ($term) {
        $ids = array_merge([$term->term_id], array_map('intval', (array) get_term_children($term->term_id, 'product_cat')));
    }
    $span = dms_price_span($ids);
    $step = $span ? dms_price_step($span['max']) : 0;
    if (! $span || $step <= 0 || $span['max'] - $span['min'] < $step) {
        return []; // everything costs about the same: nothing to filter
    }

    $fmt = fn (float $v) => html_entity_decode(wp_strip_all_tags(wc_price($v, ['decimals' => 0])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $bands = [];
    for ($lo = 0.0; $lo < $span['max']; $lo += $step) {
        $hi = $lo + $step;
        if ($hi <= $span['min']) {
            continue; // nothing this cheap
        }
        if (count($bands) === 3 || $hi >= $span['max']) { // the top band is open-ended, so nothing is out of reach
            $bands[] = ['label' => $fmt($lo).' and up', 'min' => $lo, 'max' => null];
            break;
        }
        $bands[] = $lo == 0
            ? ['label' => 'Under '.$fmt($hi), 'min' => null, 'max' => $hi]
            : ['label' => $fmt($lo).' – '.$fmt($hi), 'min' => $lo, 'max' => $hi];
    }

    return count($bands) > 1 ? $bands : [];
}

function dms_category_image(WP_Term $term, string $size = 'woocommerce_thumbnail'): string
{
    $id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
    if (! $id) {
        $products = wc_get_products(['status' => 'publish', 'category' => [$term->slug], 'limit' => 5, 'orderby' => 'date', 'order' => 'DESC']);
        foreach ($products as $product) {
            if ($id = (int) $product->get_image_id()) {
                break;
            }
        }
    }

    return $id ? (string) wp_get_attachment_image($id, $size, false, ['class' => 'dms-tile__img', 'loading' => 'lazy', 'alt' => '']) : '';
}

/* ---------------------------------------------------------------------------------------
 * Renderers
 * ------------------------------------------------------------------------------------ */

function dms_render_product_search(array $attributes): string
{
    static $n = 0;
    $id = 'dms-search-'.(++$n);
    $placeholder = (string) ($attributes['placeholder'] ?? 'Search tricks');
    $value = is_search() ? get_search_query() : '';

    return '<form role="search" method="get" class="wp-block-divimagicshop-product-search dms-search" action="'.esc_url(home_url('/')).'">'
        .'<label class="screen-reader-text" for="'.$id.'">Search products</label>'
        .'<input id="'.$id.'" class="dms-search__input" type="search" name="s" value="'.$value.'" placeholder="'.esc_attr($placeholder).'" autocomplete="off">'
        .'<input type="hidden" name="post_type" value="product">'
        .'<button class="dms-search__button" type="submit" aria-label="Search"><span aria-hidden="true"></span></button>'
        .'</form>';
}

function dms_render_category_bar(): string
{
    if (! function_exists('wc_get_page_permalink')) {
        return dms_editor_placeholder('The category bar needs WooCommerce.');
    }
    $current = dms_current_category();
    $currentTop = $current;
    while ($currentTop && $currentTop->parent) {
        $currentTop = get_term($currentTop->parent, 'product_cat');
        $currentTop = $currentTop instanceof WP_Term ? $currentTop : null;
    }

    $isShop = is_shop() && ! is_search();
    $items = '<li><a href="'.esc_url(wc_get_page_permalink('shop')).'"'.($isShop ? ' aria-current="page"' : '').'>Shop all</a></li>';
    foreach (dms_top_categories() as $term) {
        $link = get_term_link($term);
        if (is_wp_error($link)) {
            continue;
        }
        $on = $currentTop && $currentTop->term_id === $term->term_id;
        $items .= '<li><a href="'.esc_url($link).'"'.($on ? ' aria-current="page"' : '').'>'.esc_html($term->name).'</a></li>';
    }

    return '<nav class="wp-block-divimagicshop-category-bar dms-catbar" aria-label="Product categories"><ul>'.$items.'</ul></nav>';
}

function dms_render_category_tiles(array $attributes = []): string
{
    if (! function_exists('wc_get_products')) {
        return dms_editor_placeholder('Category tiles need WooCommerce.');
    }
    $terms = array_slice(dms_top_categories(), 0, max(1, (int) ($attributes['count'] ?? 8)));
    if (! $terms) {
        return dms_editor_placeholder('Category tiles appear here once products have categories.');
    }

    $tiles = '';
    foreach ($terms as $term) {
        $link = get_term_link($term);
        if (is_wp_error($link)) {
            continue;
        }
        $count = (int) $term->count;
        $tiles .= '<li class="dms-tile"><a class="dms-tile__link" href="'.esc_url($link).'">'
            .'<span class="dms-tile__media">'.dms_category_image($term).'</span>'
            .'<span class="dms-tile__body"><span class="dms-tile__name">'.esc_html($term->name).'</span>'
            .'<span class="dms-tile__count">'.esc_html(sprintf(_n('%d trick', '%d tricks', $count, 'divimagicshop'), $count)).'</span></span>'
            .'</a></li>';
    }

    return '<ul class="wp-block-divimagicshop-category-tiles dms-tiles">'.$tiles.'</ul>';
}

function dms_render_spotlight(): string
{
    if (! function_exists('wc_get_products')) {
        return dms_editor_placeholder('The spotlight needs WooCommerce.');
    }
    $base = ['status' => 'publish', 'visibility' => 'catalog', 'stock_status' => 'instock', 'limit' => 6];
    $label = 'Featured';
    $candidates = wc_get_products($base + ['featured' => true, 'orderby' => 'menu_order', 'order' => 'ASC']);
    if (! $candidates) {
        $label = 'Just in';
        $candidates = wc_get_products($base + ['orderby' => 'date', 'order' => 'DESC']);
    }
    $product = null;
    foreach ($candidates as $c) {
        if ($c->get_image_id()) {
            $product = $c;
            break;
        }
    }
    if (! $product) {
        return dms_editor_placeholder('A product with a picture appears here.');
    }

    $link = esc_url($product->get_permalink());

    return '<a class="wp-block-divimagicshop-spotlight dms-spot" href="'.$link.'">'
        .'<span class="dms-spot__media">'.$product->get_image('woocommerce_single', ['class' => 'dms-spot__img', 'loading' => 'eager'])
        .'<span class="dms-badge dms-badge--ink">'.esc_html($label).'</span></span>'
        .'<span class="dms-spot__body">'
        .'<span class="dms-spot__name">'.esc_html($product->get_name()).'</span>'
        .'<span class="dms-spot__price">'.wp_kses_post($product->get_price_html()).'</span>'
        .'</span></a>';
}

function dms_render_product_grid(array $attributes): string
{
    if (! function_exists('wc_get_products')) {
        return dms_editor_placeholder('Product grids need WooCommerce.');
    }
    $source = (string) ($attributes['source'] ?? 'new');
    $count = max(1, min(24, (int) ($attributes['count'] ?? 8)));
    $args = ['status' => 'publish', 'visibility' => 'catalog', 'limit' => $count, 'orderby' => 'date', 'order' => 'DESC'];
    $more = wc_get_page_permalink('shop');

    if ($source === 'sale') {
        $ids = wc_get_product_ids_on_sale();
        if (! $ids) {
            return dms_editor_placeholder('No products are on sale, so this grid is hidden.');
        }
        $args['include'] = $ids;
        $more = '';
    } elseif ($source === 'featured') {
        $args['featured'] = true;
        $more = '';
    }

    $products = wc_get_products($args);
    if (! $products) {
        return dms_editor_placeholder('No products to show here yet.');
    }
    $title = (string) ($attributes['title'] ?? '');

    return '<section class="wp-block-divimagicshop-product-grid dms-shelf">'
        .($title !== '' ? '<header class="dms-shelf__head"><h2 class="dms-shelf__title">'.esc_html($title).'</h2>'
            .($more ? '<a class="dms-more" href="'.esc_url($more).'">Shop all</a>' : '').'</header>' : '')
        .dms_product_row($products).'</section>';
}

function dms_render_shop_filters(): string
{
    if (! function_exists('wc_get_page_permalink')) {
        return dms_editor_placeholder('Shop filters need WooCommerce.');
    }
    $current = dms_current_category();
    $f = dms_active_filters();

    // Categories: all top-level ones; the open branch shows its subcategories.
    $open = [];
    for ($t = $current; $t instanceof WP_Term; $t = $t->parent ? get_term($t->parent, 'product_cat') : null) {
        $open[] = $t->term_id;
    }
    $cats = '<li><a href="'.esc_url(dms_filter_url(['min_price' => null, 'max_price' => null], true)).'"'.(! $current && ! $f['s'] ? ' aria-current="page"' : '').'>All tricks</a></li>';
    $branch = function (WP_Term $term) use (&$branch, $current, $open): string {
        $link = get_term_link($term);
        if (is_wp_error($link)) {
            return '';
        }
        $on = $current && $current->term_id === $term->term_id;
        $html = '<li><a href="'.esc_url($link).'"'.($on ? ' aria-current="page"' : '').'>'
            .'<span>'.esc_html($term->name).'</span><span class="dms-filters__count">'.(int) $term->count.'</span></a>';
        if (in_array($term->term_id, $open, true)) {
            $kids = get_terms(['taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => true, 'menu_order' => 'ASC']);
            if (! is_wp_error($kids) && $kids) {
                $html .= '<ul>'.implode('', array_map($branch, $kids)).'</ul>';
            }
        }

        return $html.'</li>';
    };
    foreach (dms_top_categories() as $term) {
        $cats .= $branch($term);
    }

    $html = '<div class="dms-filters__group"><h2 class="dms-filters__title">Categories</h2><ul class="dms-filters__cats">'.$cats.'</ul></div>';

    // Price bands.
    $bands = dms_price_bands($current);
    if ($bands) {
        $anyPrice = $f['min_price'] === null && $f['max_price'] === null;
        $items = '<li><a href="'.esc_url(dms_filter_url(['min_price' => null, 'max_price' => null])).'"'.($anyPrice ? ' aria-current="true"' : '').'>Any price</a></li>';
        foreach ($bands as $band) {
            $on = ! $anyPrice && $f['min_price'] == $band['min'] && $f['max_price'] == $band['max'];
            $url = dms_filter_url(['min_price' => $band['min'], 'max_price' => $band['max']]);
            $items .= '<li><a href="'.esc_url($url).'"'.($on ? ' aria-current="true"' : '').'>'.esc_html($band['label']).'</a></li>';
        }
        $html .= '<div class="dms-filters__group"><h2 class="dms-filters__title">Price</h2><ul class="dms-filters__options">'.$items.'</ul></div>';
    }

    // Availability.
    $stockUrl = dms_filter_url(['in_stock' => $f['in_stock'] ? null : 1]);
    $html .= '<div class="dms-filters__group"><h2 class="dms-filters__title">Availability</h2><ul class="dms-filters__options">'
        .'<li><a class="dms-filters__check" href="'.esc_url($stockUrl).'" role="checkbox" aria-checked="'.($f['in_stock'] ? 'true' : 'false').'">In stock only</a></li></ul></div>';

    $active = ($f['min_price'] !== null || $f['max_price'] !== null ? 1 : 0) + ($f['in_stock'] ? 1 : 0);

    // A disclosure on phones (opens on wider screens straight away, see blocks.css / the inline script).
    return '<details class="wp-block-divimagicshop-shop-filters dms-filters">'
        .'<summary class="dms-filters__toggle">Categories &amp; filters'.($active ? ' <span class="dms-filters__badge">'.$active.'</span>' : '').'</summary>'
        .'<div class="dms-filters__body">'.$html.'</div></details>'
        .'<script>(function(d){if(d&&window.matchMedia("(min-width: 901px)").matches){d.open=true;}})(document.currentScript.previousElementSibling);</script>';
}

function dms_render_active_filters(): string
{
    if (! function_exists('wc_price')) {
        return '';
    }
    $f = dms_active_filters();
    $chips = [];
    $fmt = fn (float $v) => html_entity_decode(wp_strip_all_tags(wc_price($v, ['decimals' => 0])), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if ($f['s'] !== '') {
        $chips[] = ['“'.$f['s'].'”', dms_filter_url([], true)];
    }
    if ($f['min_price'] !== null || $f['max_price'] !== null) {
        $label = match (true) {
            $f['min_price'] === null => 'Under '.$fmt($f['max_price']),
            $f['max_price'] === null => $fmt($f['min_price']).' and up',
            default => $fmt($f['min_price']).' – '.$fmt($f['max_price']),
        };
        $chips[] = [$label, dms_filter_url(['min_price' => null, 'max_price' => null])];
    }
    if ($f['in_stock']) {
        $chips[] = ['In stock', dms_filter_url(['in_stock' => null])];
    }
    if (! $chips) {
        return dms_editor_placeholder('Filters in use show here (search, price, in stock).');
    }

    $html = '';
    foreach ($chips as [$label, $url]) {
        $html .= $url
            ? '<li><a class="dms-chip" href="'.esc_url($url).'" aria-label="'.esc_attr('Remove '.$label).'">'.esc_html($label).'<span class="dms-chip__x" aria-hidden="true"></span></a></li>'
            : '<li><span class="dms-chip">'.esc_html($label).'</span></li>';
    }
    if (count($chips) > 1) {
        $html .= '<li><a class="dms-chips__clear" href="'.esc_url($f['s'] !== '' ? wc_get_page_permalink('shop') : dms_filter_url(['min_price' => null, 'max_price' => null, 'in_stock' => null])).'">Clear all</a></li>';
    }

    return '<ul class="wp-block-divimagicshop-active-filters dms-chips" aria-label="Filters in use">'.$html.'</ul>';
}
