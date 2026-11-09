<?php
/**
 * Placeholder content: pages, tutorials and FAQs, so a fresh site looks complete.
 *
 * Safe to run again: anything that already exists (by slug) is left as it is, so edits
 * made in the admin are never overwritten. Run it from Tools → Divimagicshop, or
 *   wp divimagicshop seed
 */

defined('ABSPATH') || exit;

const DMS_LOREM = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus. Donec ullamcorper nulla non metus auctor fringilla. Vestibulum id ligula porta felis euismod semper.';
const DMS_LOREM_2 = 'Cras mattis consectetur purus sit amet fermentum. Maecenas faucibus mollis interdum. Nullam quis risus eget urna mollis ornare vel eu leo. Aenean lacinia bibendum nulla sed consectetur.';

function dms_blocks_paragraphs(array $paragraphs): string
{
    return implode("\n\n", array_map(fn ($p) => "<!-- wp:paragraph -->\n<p>".esc_html($p)."</p>\n<!-- /wp:paragraph -->", $paragraphs));
}

function dms_block_heading(string $text): string
{
    return "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">".esc_html($text)."</h2>\n<!-- /wp:heading -->";
}

function dms_lorem_page(array $sections): string
{
    $out = [dms_blocks_paragraphs([DMS_LOREM])];
    foreach ($sections as $heading) {
        $out[] = dms_block_heading($heading);
        $out[] = dms_blocks_paragraphs([DMS_LOREM_2, DMS_LOREM]);
    }

    return implode("\n\n", $out);
}

/** @return array{created: string[], skipped: int} */
function dms_seed(): array
{
    $created = [];
    $skipped = 0;

    $make = function (string $type, string $slug, string $title, string $content, array $extra = []) use (&$created, &$skipped): int {
        $existing = get_posts(['post_type' => $type, 'name' => $slug, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids']);
        if ($existing) {
            $skipped++;

            return (int) $existing[0];
        }
        $id = wp_insert_post($extra + [
            'post_type' => $type,
            'post_name' => $slug,
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
        ], true);
        if (is_wp_error($id)) {
            return 0;
        }
        $created[] = "$type: $title";

        return (int) $id;
    };

    // Pages
    $make('page', 'about', 'About us', dms_lorem_page(['Our story', 'What we stand for']));
    $make('page', 'contact', 'Contact', dms_blocks_paragraphs([DMS_LOREM_2])
        ."\n\n".dms_block_heading('Get in touch')
        ."\n\n".dms_blocks_paragraphs(['Email: hello@example.com', 'Phone: +00 000 000 0000', 'Monday to Friday, 9:00 – 18:00']));
    $make('page', 'shipping-returns', 'Shipping & Returns', dms_lorem_page(['Shipping', 'Delivery times', 'Returns', 'Refunds']));
    $make('page', 'terms', 'Terms & Conditions', dms_lorem_page(['Using this site', 'Orders and payment', 'Liability']));
    $privacy = (int) get_option('wp_page_for_privacy_policy');
    if (! $privacy || get_post_status($privacy) !== 'publish') {
        $privacy = $make('page', 'privacy-policy', 'Privacy Policy', dms_lorem_page(['What we collect', 'How we use it', 'Your rights']));
        if ($privacy) {
            wp_update_post(['ID' => $privacy, 'post_status' => 'publish']);
            update_option('wp_page_for_privacy_policy', $privacy);
        }
    }

    // Tutorials
    $tutorials = [
        'your-first-double-lift' => ['Your first double lift', 'The move behind a hundred card tricks, broken down step by step.'],
        'the-classic-force' => ['The classic force', 'Make a spectator "freely" choose the card you want.'],
        'overhand-shuffle-control' => ['Overhand shuffle control', 'Keep a chosen card on top while the deck looks thoroughly mixed.'],
        'coin-vanish-basics' => ['Coin vanish basics', 'Three beginner vanishes and when to use each one.'],
        'performing-for-a-crowd' => ['Performing for a crowd', 'Angles, pacing and patter for when more people are watching.'],
        'build-a-five-minute-routine' => ['Build a five-minute routine', 'Put three tricks together into a set with a strong finish.'],
    ];
    $tutorialIds = [];
    foreach ($tutorials as $slug => [$title, $excerpt]) {
        $tutorialIds[] = $make('dms_tutorial', $slug, $title,
            dms_blocks_paragraphs([DMS_LOREM])
            ."\n\n".dms_block_heading('Step by step')
            ."\n\n".dms_blocks_paragraphs([DMS_LOREM_2, DMS_LOREM, DMS_LOREM_2])
            ."\n\n".dms_block_heading('Tips')
            ."\n\n".dms_blocks_paragraphs([DMS_LOREM]),
            ['post_excerpt' => $excerpt]);
    }
    $linked = dms_seed_link_tutorials(array_filter($tutorialIds));

    // FAQ
    $topics = [];
    foreach (['Orders', 'Shipping', 'Products', 'Returns'] as $name) {
        $term = term_exists($name, 'dms_faq_topic') ?: wp_insert_term($name, 'dms_faq_topic');
        $topics[$name] = is_wp_error($term) ? 0 : (int) $term['term_id'];
    }
    $faqs = [
        ['how-do-i-place-an-order', 'How do I place an order?', 'Orders'],
        ['which-payment-methods', 'Which payment methods do you accept?', 'Orders'],
        ['can-i-change-my-order', 'Can I change or cancel my order?', 'Orders'],
        ['how-long-does-delivery-take', 'How long does delivery take?', 'Shipping'],
        ['do-you-ship-internationally', 'Do you ship internationally?', 'Shipping'],
        ['are-the-tricks-beginner-friendly', 'Are the tricks beginner friendly?', 'Products'],
        ['do-tricks-come-with-instructions', 'Do tricks come with instructions?', 'Products'],
        ['what-is-your-return-policy', 'What is your return policy?', 'Returns'],
    ];
    foreach ($faqs as $order => [$slug, $question, $topic]) {
        $id = $make('dms_faq', $slug, $question, dms_blocks_paragraphs([DMS_LOREM_2]), ['menu_order' => $order]);
        if ($id && $topics[$topic] && ! wp_get_post_terms($id, 'dms_faq_topic', ['fields' => 'ids'])) {
            wp_set_post_terms($id, [$topics[$topic]], 'dms_faq_topic');
        }
    }

    // Site identity (only while still WordPress's defaults)
    if (in_array(get_option('blogname'), ['', 'My WordPress Blog', 'WordPress'], true)) {
        update_option('blogname', 'Divimagicshop');
    }
    if (in_array(get_option('blogdescription'), ['', 'Just another WordPress site'], true)) {
        update_option('blogdescription', 'Magic, delivered.');
    }

    flush_rewrite_rules(false);

    if ($linked) {
        $created[] = "linked $linked tutorial(s) to products";
    }

    return ['created' => $created, 'skipped' => $skipped];
}

/**
 * Give each tutorial that has no products two of the shop's products (round robin), so
 * "What you'll need" and "Learn it" have something to show. Products sync in after the
 * first seed, so running the seed again later links them.
 */
function dms_seed_link_tutorials(array $tutorialIds): int
{
    $products = get_posts(['post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 50, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids']);
    if (! $products) {
        return 0;
    }

    $linked = 0;
    foreach (array_values($tutorialIds) as $i => $tutorialId) {
        if (dms_tutorial_product_ids($tutorialId)) {
            continue;
        }
        $pick = [$products[$i % count($products)], $products[($i + 1) % count($products)]];
        dms_set_tutorial_products($tutorialId, $pick);
        $linked++;
    }

    return $linked;
}

// Tools → Divimagicshop
add_action('admin_menu', function () {
    add_management_page('Divimagicshop', 'Divimagicshop', 'manage_options', 'divimagicshop', 'dms_tools_page');
});

function dms_tools_page(): void
{
    $result = get_transient('dms_seed_result_'.get_current_user_id());
    delete_transient('dms_seed_result_'.get_current_user_id());

    echo '<div class="wrap"><h1>Divimagicshop</h1>';
    if (is_array($result)) {
        echo '<div class="notice notice-success"><p>';
        echo $result['created']
            ? 'Added: '.esc_html(implode(', ', $result['created'])).'.'
            : 'Nothing to add — all placeholder content is already there.';
        echo '</p></div>';
    }
    echo '<p>Adds the placeholder pages (About, Contact, Shipping &amp; Returns, Terms, Privacy), 6 tutorials and 8 FAQ questions.</p>';
    echo '<p>Anything that already exists is left alone, so your edits are safe. Run it again after the first product sync to link tutorials to products.</p>';
    echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
    wp_nonce_field('dms_seed');
    echo '<input type="hidden" name="action" value="dms_seed">';
    submit_button('Add placeholder content', 'primary', 'submit', false);
    echo '</form></div>';
}

add_action('admin_post_dms_seed', function () {
    if (! current_user_can('manage_options')) {
        wp_die('Not allowed.', 403);
    }
    check_admin_referer('dms_seed');
    set_transient('dms_seed_result_'.get_current_user_id(), dms_seed(), 60);
    wp_safe_redirect(admin_url('tools.php?page=divimagicshop'));
    exit;
});

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('divimagicshop seed', function () {
        $result = dms_seed();
        foreach ($result['created'] as $line) {
            WP_CLI::log("Added $line");
        }
        WP_CLI::success(sprintf('%d added, %d already there.', count($result['created']), $result['skipped']));
    }, ['shortdesc' => 'Add the placeholder pages, tutorials and FAQs (never overwrites).']);
}
