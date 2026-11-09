#!/usr/bin/env bash
# Build a local Divimagicshop at http://divimagicshop.test (Laragon) from a fresh clone:
# WordPress + WooCommerce (pinned), the JL Magic storefront plugin, this theme, and the
# placeholder content. Safe to run again: each step is skipped when its result exists.
#
#   bash scripts/setup.sh
#   JLMAGIC_KEY=jlm_... bash scripts/setup.sh     # also connect to the app and sync
#
# The plugin zip comes from the jlmagic-wp checkout next to this one
# (override with PLUGIN_ZIP=/path/to/jlmagic-storefront.zip).
set -euo pipefail
source "$(dirname "$0")/lib.sh"
require_local

[ -f "$PLUGIN_ZIP" ] || die "Plugin zip not found at $PLUGIN_ZIP. Clone jl-magic-wp next to this folder, or set PLUGIN_ZIP."

say "Database $DB_NAME"
db_query "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

if [ ! -f "$ROOT/wp-load.php" ]; then
  say "Downloading WordPress $WP_VERSION"
  wp core download --version="$WP_VERSION" --skip-content
fi
mkdir -p "$ROOT/wp-content/plugins" "$ROOT/wp-content/uploads"

if [ ! -f "$ROOT/wp-config.php" ]; then
  say "Writing wp-config.php"
  wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="$DB_HOST:$DB_PORT" --skip-check --quiet
  wp config set WP_ENVIRONMENT_TYPE local --quiet
  wp config set WP_DEBUG true --raw --quiet
  wp config set WP_DEBUG_LOG true --raw --quiet
  wp config set WP_DEBUG_DISPLAY false --raw --quiet
fi

if ! wp core is-installed 2>/dev/null; then
  say "Installing WordPress at $SITE_URL"
  wp core install --url="$SITE_URL" --title="Divimagicshop" \
    --admin_user=admin --admin_password=admin --admin_email=admin@example.test --skip-email
fi
# On Windows, `wp core install` can save the URL with the folder path glued on, so set both.
wp_safe option update home "$SITE_URL" --quiet
wp_safe option update siteurl "$SITE_URL" --quiet

say "Theme: Divimagicshop"
wp_safe theme activate divimagicshop --quiet

if ! wp_safe plugin is-installed woocommerce; then
  say "Installing WooCommerce $WC_VERSION"
  wp_safe plugin install woocommerce --version="$WC_VERSION" --activate
elif ! wp_safe plugin is-active woocommerce; then
  wp_safe plugin activate woocommerce
fi

if ! wp_safe plugin is-installed jlmagic-storefront; then
  say "Installing JL Magic Storefront from $PLUGIN_ZIP"
  wp_safe plugin install "$(winpath "$PLUGIN_ZIP")" --activate
elif ! wp_safe plugin is-active jlmagic-storefront; then
  wp_safe plugin activate jlmagic-storefront
fi

say "Store settings (Philippines, cash on delivery, no setup wizard, shop open)"
wp_safe option update woocommerce_default_country PH --quiet
wp_safe option update woocommerce_onboarding_profile '{"skipped":true}' --format=json --quiet
wp_safe option update woocommerce_task_list_hidden_lists '["setup"]' --format=json --quiet
wp_safe option update woocommerce_cod_settings \
  '{"enabled":"yes","title":"Cash on delivery","description":"Pay with cash upon delivery."}' --format=json --quiet
wp_safe option update woocommerce_coming_soon no --quiet
wp_safe rewrite structure '/%postname%/' --hard --quiet

say "Placeholder pages, tutorials and FAQ"
wp_safe divimagicshop seed

if [ -n "${JLMAGIC_KEY:-}" ]; then
  say "Connecting to the JL Magic app and syncing"
  JLMAGIC_KEY="$JLMAGIC_KEY" wp eval '
    $key = JLMagic\Storefront\Key::parse(getenv("JLMAGIC_KEY"));
    $site = JLMagic\Storefront\Plugin::client($key)->site();
    update_option(JLMagic\Storefront\Plugin::KEY_OPTION, $key->token(), false);
    JLMagic\Storefront\Plugin::rememberSite($site);
    JLMagic\Storefront\Plugin::schedule();
    $r = JLMagic\Storefront\Plugin::sync();
    echo $r["error"] ? "Sync error: {$r["error"]}\n" : "Synced {$r["seen"]} products ({$r["changed"]} changed).\n";
  '
  # Link the placeholder tutorials to the products that just arrived.
  wp_safe divimagicshop seed
fi

cat <<EOF

Done. Your local Divimagicshop:
  Shop:      $SITE_URL
  Admin:     $SITE_URL/wp-admin   (login: admin / admin)
  Products:  $SITE_URL/wp-admin/admin.php?page=jlmagic-storefront  (paste a key, click Sync)

If the URL doesn't open, click Laragon → Reload.
EOF
