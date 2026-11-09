<?php
/**
 * Divimagicshop theme. Almost everything is in theme.json, templates/, parts/ and patterns/;
 * this file loads the stylesheet, registers the curtain block style, and trims WooCommerce
 * output the design doesn't use.
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_editor_style('style.css');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('divimagicshop', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
});

add_action('init', function () {
    register_block_style('core/group', ['name' => 'curtain', 'label' => 'Curtain (red bottom edge)']);
    register_block_pattern_category('divimagicshop', ['label' => 'Divimagicshop']);
});

// Product page: the option picker already shows the attributes, and the shop doesn't take
// reviews, so the description is the only tab — shown as a plain section titled "The trick".
add_filter('woocommerce_product_tabs', function (array $tabs): array {
    unset($tabs['additional_information'], $tabs['reviews']);
    if (isset($tabs['description'])) {
        $tabs['description']['title'] = 'The trick';
    }

    return $tabs;
}, 20);
add_filter('woocommerce_product_description_heading', fn () => 'The trick');
