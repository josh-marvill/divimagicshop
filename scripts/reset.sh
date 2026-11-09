#!/usr/bin/env bash
# Delete this LOCAL copy completely: its database and every file in this folder that git
# doesn't track (WordPress core, uploads, other plugins). The theme, the core plugin,
# scripts and README are tracked, so they are kept.
#
#   bash scripts/reset.sh      then   bash scripts/setup.sh
set -euo pipefail
source "$(dirname "$0")/lib.sh"
require_local

echo "This deletes the $DB_NAME database and every file in $ROOT that git doesn't track"
echo "(WordPress, uploads, products, orders). The theme and scripts are kept."
read -r -p "Type yes to delete: " answer || answer=""
if [ "$answer" != "yes" ]; then
  echo "Nothing deleted."
  exit 0
fi

say "Dropping database $DB_NAME"
db_query "DROP DATABASE IF EXISTS \`$DB_NAME\`"

say "Removing WordPress files"
git -C "$ROOT" clean -fdX --quiet

echo "Run scripts/setup.sh to start over."
