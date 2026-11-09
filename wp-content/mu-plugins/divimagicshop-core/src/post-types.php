<?php
/**
 * Tutorials (dms_tutorial) and FAQ (dms_faq, grouped by dms_faq_topic).
 */

defined('ABSPATH') || exit;

add_action('init', 'dms_register_post_types');

function dms_register_post_types(): void
{
    register_post_type('dms_tutorial', [
        'labels' => [
            'name' => 'Tutorials',
            'singular_name' => 'Tutorial',
            'add_new_item' => 'Add tutorial',
            'edit_item' => 'Edit tutorial',
            'all_items' => 'All tutorials',
            'search_items' => 'Search tutorials',
            'not_found' => 'No tutorials yet.',
        ],
        'public' => true,
        'has_archive' => 'tutorials',
        'rewrite' => ['slug' => 'tutorials', 'with_front' => false],
        'menu_icon' => 'dashicons-video-alt3',
        'menu_position' => 25,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
    ]);

    register_post_type('dms_faq', [
        'labels' => [
            'name' => 'FAQ',
            'singular_name' => 'Question',
            'add_new_item' => 'Add question',
            'edit_item' => 'Edit question',
            'all_items' => 'All questions',
            'not_found' => 'No questions yet.',
        ],
        'public' => true,
        'publicly_queryable' => true,
        'has_archive' => 'faq',
        'rewrite' => ['slug' => 'faq', 'with_front' => false],
        'menu_icon' => 'dashicons-editor-help',
        'menu_position' => 26,
        'show_in_rest' => true,
        // The title is the question, the content is the answer; "Order" sorts within a topic.
        'supports' => ['title', 'editor', 'page-attributes'],
    ]);

    register_taxonomy('dms_faq_topic', 'dms_faq', [
        'labels' => [
            'name' => 'Topics',
            'singular_name' => 'Topic',
            'add_new_item' => 'Add topic',
        ],
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'hierarchical' => true,
    ]);

    // mu-plugins have no activation hook: flush rewrite rules once per version, so
    // /tutorials/ and /faq/ work right after a deploy.
    if (get_option('dms_core_rewrite_version') !== DMS_CORE_VERSION) {
        add_action('wp_loaded', function () {
            if (wp_installing() || ! is_blog_installed()) {
                return; // no tables yet (e.g. before `wp core install`)
            }
            flush_rewrite_rules(false);
            update_option('dms_core_rewrite_version', DMS_CORE_VERSION);
        });
    }
}

// A single FAQ entry has no page of its own: send it to its place on the FAQ page.
add_action('template_redirect', function () {
    if (is_singular('dms_faq')) {
        wp_safe_redirect(get_post_type_archive_link('dms_faq').'#faq-'.get_queried_object_id(), 301);
        exit;
    }
});

// The FAQ page lists everything (the block does the grouping), and in topic order.
add_action('pre_get_posts', function (WP_Query $query) {
    if (! is_admin() && $query->is_main_query() && $query->is_post_type_archive('dms_faq')) {
        $query->set('posts_per_page', -1);
    }
});
