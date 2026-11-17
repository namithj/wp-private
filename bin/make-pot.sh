#!/usr/bin/env bash
#
# Extract the translatable strings with WP-CLI and fail when that does not work.
#
#   bin/make-pot.sh                          extract to languages/wp-private.pot
#   bin/make-pot.sh .cache/wp-private.pot    extract to another file
#
# Needs WP-CLI (https://wp-cli.org). Choose another binary with WP.

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

WP="${WP:-wp}"
OUT="${1:-languages/wp-private.pot}"

if ! command -v "${WP}" >/dev/null 2>&1; then
	echo "WP-CLI (${WP}) not found." >&2
	exit 1
fi

mkdir -p "$(dirname "${OUT}")"
rm -f "${OUT}"

"${WP}" i18n make-pot . "${OUT}" \
	--slug=wp-private \
	--domain=wp-private \
	--exclude=vendor,vendor-prefixed,tests,dist,.cache,.ai

# A pot without the plugin's own strings means the extraction silently found nothing.
if [[ ! -s "${OUT}" ]] || ! grep -q 'msgid "You do not have permission to view this content."' "${OUT}"; then
	echo "The extracted .pot is missing or does not contain WP Private's own strings: ${OUT}" >&2
	exit 1
fi

echo "OK: $(grep -c '^msgid ' "${OUT}") strings in ${OUT}"
