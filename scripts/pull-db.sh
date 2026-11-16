#!/usr/bin/env bash
#
# Copy a server's database (and optionally uploads) into your local wp-env site.
# Content only moves DOWN: server → local. Never run the reverse.
#
# Usage (Git Bash or WSL, from the repo root):
#   bash scripts/pull-db.sh             # database only
#   bash scripts/pull-db.sh --uploads   # database + wp-content/uploads
#
# Settings come from a .env file in the repo root (it is gitignored). Example:
#   REMOTE_SSH="user@dsgourakis.com"
#   REMOTE_SSH_PORT="22"
#   REMOTE_WP_PATH="staging.dsgourakis.com"     # folder containing wp-config.php
#   REMOTE_URL="https://staging.dsgourakis.com"
#   LOCAL_URL="http://localhost:8888"
#
# Requires WP-CLI ("wp") on the server. If your host does not have it,
# use the WP Migrate plugin instead (see README).

set -euo pipefail

if [ ! -f .env ]; then
	echo "Missing .env in the repo root. See the comments at the top of this script."
	exit 1
fi
# shellcheck disable=SC1091
source .env

: "${REMOTE_SSH:?Set REMOTE_SSH in .env}"
: "${REMOTE_WP_PATH:?Set REMOTE_WP_PATH in .env}"
: "${REMOTE_URL:?Set REMOTE_URL in .env}"
LOCAL_URL="${LOCAL_URL:-http://localhost:8888}"
PORT="${REMOTE_SSH_PORT:-22}"
DUMP="pulled-$(date +%Y%m%d-%H%M%S).sql"

echo "1/5 Backing up your current local database to local-backup.sql"
npx wp-env run cli wp db export wp-content/dsg-repo/local-backup.sql

echo "2/5 Exporting the database on the server"
ssh -p "$PORT" "$REMOTE_SSH" "cd '$REMOTE_WP_PATH' && wp db export - --single-transaction" > "$DUMP"

echo "3/5 Importing into the local site"
# The repo is mounted inside the container at wp-content/dsg-repo.
npx wp-env run cli wp db import "wp-content/dsg-repo/$DUMP"

echo "4/5 Replacing $REMOTE_URL with $LOCAL_URL (safe for serialized data)"
npx wp-env run cli wp search-replace "$REMOTE_URL" "$LOCAL_URL" --all-tables --precise --skip-columns=guid

if [ "${1:-}" = "--uploads" ]; then
	echo "5/5 Downloading uploads"
	mkdir -p uploads
	scp -P "$PORT" -r "$REMOTE_SSH:$REMOTE_WP_PATH/wp-content/uploads/." uploads/
	echo "Uploads saved to ./uploads. Map them into wp-env by adding this to .wp-env.override.json:"
	echo '  { "mappings": { "wp-content/uploads": "./uploads" } }'
else
	echo "5/5 Skipping uploads (add --uploads to copy them)"
fi

rm -f "$DUMP"
npx wp-env run cli wp cache flush
echo "Done. Log in at $LOCAL_URL/wp-admin with the SERVER's username and password."
