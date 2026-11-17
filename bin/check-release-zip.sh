#!/usr/bin/env bash
#
# Assert on the contents of a release ZIP.
#
#   bin/check-release-zip.sh dist/wp-private-2.0.0.zip
#   bin/check-release-zip.sh --self-test        build a bad ZIP and confirm it is rejected
#
# Fails when the ZIP lacks a runtime file or contains planning files, tests,
# tooling or dev dependencies.

set -euo pipefail

SLUG="wp-private"

# Must be in the ZIP.
REQUIRED=( "${SLUG}.php" "readme.txt" "composer.json" "includes/" "languages/" "vendor/autoload.php" "vendor-prefixed/autoload.php" "vendor-prefixed/pedalcms/" )

# Must not be in the ZIP.
FORBIDDEN=( ".ai/" "tests/" "bin/" ".github/" ".wordpress-org/" "node_modules/" "composer.lock" "vendor/bin/" "vendor/pedalcms/" "vendor/phpunit/" "vendor/squizlabs/" "vendor/brianhenryie/" "phpunit.xml" "phpcs.xml" "phpstan.neon" "screenshot-" ".distignore" ".gitignore" ".gitattributes" ".git/" "SECURITY.md" "dist/" "vendor-prefixed/pedalcms/cassette-cmf-block-builder/tests/" )

check_zip() {
	local zip="$1" entries failed=0 path
	entries="$( unzip -Z1 "${zip}" )"
	for path in "${REQUIRED[@]}"; do
		if ! grep -q "^${SLUG}/${path}" <<< "${entries}"; then
			echo "Missing from the release ZIP: ${path}" >&2
			failed=1
		fi
	done
	for path in "${FORBIDDEN[@]}"; do
		if grep -q "^${SLUG}/${path}" <<< "${entries}"; then
			echo "Must not be in the release ZIP: ${path}" >&2
			failed=1
		fi
	done
	return "${failed}"
}

if [[ "${1:-}" == "--self-test" ]]; then
	tmp="$( mktemp -d )"
	trap 'rm -rf "${tmp}"' EXIT
	mkdir -p "${tmp}/${SLUG}/includes" "${tmp}/${SLUG}/.ai" "${tmp}/${SLUG}/tests"
	touch "${tmp}/${SLUG}/${SLUG}.php" "${tmp}/${SLUG}/.ai/notes.md" "${tmp}/${SLUG}/tests/a.php" "${tmp}/${SLUG}/composer.lock"
	( cd "${tmp}" && zip -rq bad.zip "${SLUG}" )
	if check_zip "${tmp}/bad.zip" 2>/dev/null; then
		echo "Self-test failed: a bad ZIP was accepted." >&2
		exit 1
	fi
	echo "Self-test OK: a ZIP with .ai/, tests/, composer.lock and missing files is rejected."
	exit 0
fi

ZIP="${1:-}"
if [[ -z "${ZIP}" || ! -f "${ZIP}" ]]; then
	echo "Usage: $0 <zip> | --self-test" >&2
	exit 2
fi

if check_zip "${ZIP}"; then
	echo "OK: ${ZIP} has every runtime file and no planning, test or tooling file."
else
	echo "The release ZIP failed its content checks: check .distignore." >&2
	exit 1
fi
