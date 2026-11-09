<?php
/**
 * Divimagicshop theme. Almost everything is in theme.json, templates/, parts/ and patterns/;
 * this file only loads the stylesheet and registers the block styles the patterns use.
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
    register_block_style('core/button', ['name' => 'outline-light', 'label' => 'Outline on dark']);

    register_block_pattern_category('divimagicshop', ['label' => 'Divimagicshop']);
});
