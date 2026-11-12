<?php
/**
 * A tutorial's video URL and the products it uses.
 *
 * Products are stored one meta row per product (_dms_product_id), so "tutorials that use
 * product X" is an exact meta lookup. Edited in a plain meta box: no JS build needed.
 */

defined('ABSPATH') || exit;

const DMS_VIDEO_META = '_dms_video_url';
const DMS_PRODUCT_META = '_dms_product_id';

add_action('init', function () {
    $auth = fn () => current_user_can('edit_posts');
    register_post_meta('dms_tutorial', DMS_VIDEO_META, [
        'type' => 'string',
        'single' => true,
        'show_in_rest' => true,
        'sanitize_callback' => 'esc_url_raw',
        'auth_callback' => $auth,
    ]);
    register_post_meta('dms_tutorial', DMS_PRODUCT_META, [
        'type' => 'integer',
        'single' => false,
        'show_in_rest' => true,
        'sanitize_callback' => 'absint',
        'auth_callback' => $auth,
    ]);
});

/** @return int[] */
function dms_tutorial_product_ids(int $tutorialId): array
{
    return array_values(array_filter(array_map('intval', (array) get_post_meta($tutorialId, DMS_PRODUCT_META))));
}

function dms_set_tutorial_products(int $tutorialId, array $productIds): void
{
    delete_post_meta($tutorialId, DMS_PRODUCT_META);
    foreach (array_unique(array_filter(array_map('intval', $productIds))) as $id) {
        add_post_meta($tutorialId, DMS_PRODUCT_META, $id);
    }
}

add_action('add_meta_boxes_dms_tutorial', function () {
    add_meta_box('dms_tutorial_details', 'Video and products', 'dms_tutorial_meta_box', 'dms_tutorial', 'side');
});

function dms_tutorial_meta_box(WP_Post $post): void
{
    wp_nonce_field('dms_tutorial_meta', 'dms_tutorial_meta_nonce');
    $video = (string) get_post_meta($post->ID, DMS_VIDEO_META, true);
    $selected = dms_tutorial_product_ids($post->ID);
    $products = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'private'],
        'posts_per_page' => 500,
        'orderby' => 'title',
        'order' => 'ASC',
    ]);

    echo '<p><label for="dms_video_url"><strong>Video URL</strong></label><br>';
    echo '<input type="url" id="dms_video_url" name="dms_video_url" class="widefat" placeholder="https://www.youtube.com/watch?v=…" value="'.esc_attr($video).'"></p>';
    echo '<p class="description">YouTube or Vimeo link. Shown above the write-up.</p>';

    echo '<p><label for="dms_products"><strong>Products used</strong></label><br>';
    if (! $products) {
        echo '<em>No products yet. They appear here after the store syncs.</em></p>';

        return;
    }
    echo '<select id="dms_products" name="dms_products[]" multiple size="8" class="widefat">';
    foreach ($products as $product) {
        printf(
            '<option value="%d"%s>%s</option>',
            $product->ID,
            in_array($product->ID, $selected, true) ? ' selected' : '',
            esc_html(get_the_title($product))
        );
    }
    echo '</select></p><p class="description">Ctrl/⌘-click to pick several. The tutorial is listed on each product\'s page.</p>';
}

add_action('save_post_dms_tutorial', function (int $postId) {
    if (! isset($_POST['dms_tutorial_meta_nonce'])
        || ! wp_verify_nonce(sanitize_key(wp_unslash($_POST['dms_tutorial_meta_nonce'])), 'dms_tutorial_meta')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || ! current_user_can('edit_post', $postId)) {
        return;
    }

    $video = esc_url_raw(trim((string) wp_unslash($_POST['dms_video_url'] ?? '')));
    $video === '' ? delete_post_meta($postId, DMS_VIDEO_META) : update_post_meta($postId, DMS_VIDEO_META, $video);

    dms_set_tutorial_products($postId, array_map('intval', (array) wp_unslash($_POST['dms_products'] ?? [])));
});

// A tutorial without a featured image still shows a video frame in lists (the theme draws the play mark).
add_filter('render_block_core/post-featured-image', function (string $html, array $block, WP_Block $instance): string {
    $postId = (int) ($instance->context['postId'] ?? 0);
    if (trim($html) !== '' || ! $postId || get_post_type($postId) !== 'dms_tutorial' || is_singular('dms_tutorial')) {
        return $html;
    }

    return '<a class="dms-tut-frame" href="'.esc_url(get_permalink($postId)).'" aria-label="'.esc_attr(get_the_title($postId)).'"></a>';
}, 10, 3);
