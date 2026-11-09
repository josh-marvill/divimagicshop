# Shared helpers for the scripts in this folder. Source it, don't run it:
#   source "$(dirname "$0")/lib.sh"

say() { printf '==> %s\n' "$*"; }
die() { printf '%s\n' "$*" >&2; exit 1; }

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SITE_NAME="$(basename "$ROOT")"
SITE_URL="${SITE_URL:-http://$SITE_NAME.test}"
DB_NAME="${DB_NAME:-${SITE_NAME//-/_}}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"

# Pinned versions: every machine gets exactly these.
WP_VERSION="7.1.4"
WC_VERSION="11.2.2"

PLUGIN_ZIP="${PLUGIN_ZIP:-$ROOT/../jlmagic-wp/plugin/jlmagic-storefront.zip}"
WP_CLI_URL="https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"

# Windows-style path (C:/laragon/...) for arguments handed to the Windows php.exe.
winpath() { if command -v cygpath >/dev/null 2>&1; then cygpath -m "$1"; else printf '%s' "$1"; fi; }

php_bin() {
  if command -v php >/dev/null 2>&1; then
    command -v php
    return
  fi
  # The PHP Laragon is set to use (Menu → PHP → Version), else the newest installed.
  local version found=""
  version="$(sed -n 's/^Version=\(php-.*\)$/\1/p' /c/laragon/usr/laragon.ini 2>/dev/null | tr -d '\r' | head -n 1)"
  [ -n "$version" ] && [ -f "/c/laragon/bin/php/$version/php.exe" ] && found="/c/laragon/bin/php/$version/php.exe"
  [ -n "$found" ] || found="$(ls -d /c/laragon/bin/php/*/php.exe 2>/dev/null | sort -V | tail -n 1)"
  printf '%s' "$found"
}
PHP="$(php_bin)"
[ -n "$PHP" ] || die "PHP not found — is Laragon installed?"

# WP-CLI without loading our plugin, so a plugin build that crashes on load can't
# block setup steps or the update that replaces it.
wp_safe() { wp --skip-plugins=jlmagic-storefront "$@"; }

wp() {
  if [ ! -f "$ROOT/wp-cli.phar" ]; then
    say "Downloading WP-CLI"
    curl -fsSL "$WP_CLI_URL" -o "$ROOT/wp-cli.phar.part" || die "Couldn't download WP-CLI. Check your internet connection."
    mv "$ROOT/wp-cli.phar.part" "$ROOT/wp-cli.phar"
  fi
  # No MSYS path rewriting: arguments like '/%postname%/' must reach WP-CLI as typed.
  WP_CLI_CONFIG_PATH="$(winpath "$ROOT/scripts/wp-cli.yml")" MSYS_NO_PATHCONV=1 MSYS2_ARG_CONV_EXCL='*' "$PHP" "$(winpath "$ROOT/wp-cli.phar")" --path="$(winpath "$ROOT")" "$@"
}

# Run one SQL statement against MySQL as $DB_USER (no database selected).
db_query() {
  local rc=0
  DMS_SQL="$1" DMS_HOST="$DB_HOST" DMS_USER="$DB_USER" DMS_PASS="$DB_PASS" DMS_PORT="$DB_PORT" "$PHP" -r '
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $db = new mysqli(getenv("DMS_HOST"), getenv("DMS_USER"), (string) getenv("DMS_PASS"), null, (int) getenv("DMS_PORT"));
    } catch (mysqli_sql_exception $e) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(in_array($e->getCode(), [2002, 2003, 2006], true) ? 3 : ($e->getCode() === 1045 ? 4 : 5));
    }
    $db->query(getenv("DMS_SQL"));
  ' || rc=$?
  case "$rc" in
    0) ;;
    3) die "Start Laragon (MySQL isn't running)." ;;
    4) die "MySQL refused the login for user '$DB_USER'. Set DB_USER / DB_PASS if yours isn't root with no password." ;;
    5) die "Couldn't connect to MySQL at $DB_HOST:$DB_PORT (see the message above)." ;;
    *) die "MySQL query failed: $1" ;;
  esac
}

# These scripts build a throwaway local copy. Never let them near Cloudways.
require_local() {
  case "$SITE_URL" in
    http://*.test|http://*.test/) ;;
    *) die "These scripts only run on a local Laragon copy (*.test). Site URL is $SITE_URL." ;;
  esac
}
