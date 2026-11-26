#!/usr/bin/env bash
#
# Upgrade-path scenarios S11 and S12 (R01).
#
# For every config stored by a 1.x version, against one database and in separate PHP processes:
#   old    the 1.x files of the config's own version are active; the config is written; outputs captured
#   new    this build's files replace them; the first request upgrades; snapshots, data and the list of
#          intended changes (tests/user-configs/snapshots/upgrade/*.diff) are checked (S11)
#   down   the 1.6.1 files go back over this build's data (S12, unsupported path)
# Plus a site first installed on 2.x, then the 1.6.1 files over it (S12).
#
#   bin/test-upgrade-path.sh                run everything
#   bin/test-upgrade-path.sh 02 06          only those configs
#   WPP_UPDATE_SNAPSHOTS=1 bin/test-upgrade-path.sh   rewrite the reviewed difference lists (never in CI)
#
# Config 09 (multisite sub-site) is not part of this chain: its sub-site tables are temporary tables
# of one process. Its upgrade conversion is asserted in-process by bin/test-user-configs.sh (S11 data half).

set -uo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

PHP="${PHP:-php}"

if [[ -z "${WP_TESTS_ABSPATH:-}" ]]; then
	WP_CORE_VERSION="${WP_CORE_VERSION:-latest}"
	if ! WP_TESTS_ABSPATH="$(bash bin/install-wp-core.sh "${WP_CORE_VERSION}" "${PWD}/.cache/wp-core/${WP_CORE_VERSION}")"; then
		echo "Could not install a throwaway WordPress core." >&2
		exit 1
	fi
	export WP_TESTS_ABSPATH
fi

if [[ "${WPP_UPDATE_SNAPSHOTS:-0}" == "1" && ( -n "${CI:-}" || -n "${GITHUB_ACTIONS:-}" ) ]]; then
	echo "WPP_UPDATE_SNAPSHOTS=1 is forbidden in CI." >&2
	exit 1
fi

export WPP_UPGRADE_OUT
WPP_UPGRADE_OUT="$(mktemp -d)"
trap 'rm -rf "${WPP_UPGRADE_OUT}"' EXIT

LEGACY="${PWD}/tests/fixtures/legacy"
PHPUNIT=("${PHP}" vendor/phpunit/phpunit/phpunit --bootstrap tests/phpunit/bootstrap-user-configs.php --group upgrade-path --colors=never)

unset WP_TESTS_MULTISITE WPP_LOAD_COEXIST_PLUGIN
unset WP_TESTS_SKIP_INSTALL # The first process installs the tables.

failed=0
passed=0
start=${SECONDS}

# Run one phase for one config. $1 label, $2 phase, $3 slug, $4 plugin file ("" = this build).
run_phase() {
	local label="$1" phase="$2" slug="$3" plugin="$4" out status
	out="$(WPP_PHASE="${phase}" WPP_PLUGIN_FILE="${plugin}" "${PHPUNIT[@]}" --filter "/::test_phase with data set \"${slug}\"\$/" 2>&1)"
	status=$?
	export WP_TESTS_SKIP_INSTALL=1
	if [[ ${status} -eq 0 ]] && grep -Eq 'No tests executed|Skipped: [0-9]|Risky: [0-9]|Warnings: [0-9]|incomplete, skipped, or risky' <<<"${out}"; then
		status=1
	fi
	if [[ ${status} -eq 0 ]]; then
		echo "${label} ... PASS"
		passed=$((passed + 1))
	else
		echo "${label} ... FAIL"
		sed 's/^/    /' <<<"${out}" | head -"${WPP_FAIL_LINES:-60}"
		failed=$((failed + 1))
	fi
	return ${status}
}

slugs=()
for file in tests/user-configs/[0-9][0-9]-*.php; do
	slug="$(basename "${file}" .php)"
	[[ "${slug}" == 09-* || "${slug}" == 01-* ]] && continue
	if [[ $# -gt 0 ]]; then
		wanted=0
		for prefix in "$@"; do
			[[ "${slug}" == "${prefix}"* ]] && wanted=1
		done
		[[ ${wanted} -eq 0 ]] && continue
	fi
	slugs+=("${slug}")
done

for slug in "${slugs[@]}"; do
	version="$(grep -m1 "'version'" "tests/user-configs/${slug}.php" | sed "s/.*=> *'\([^']*\)'.*/\1/")"
	# 1.4 and 1.5 do not run on PHP 8.x: their data is written under the 1.6.1 files and their output is not captured.
	old_file="${LEGACY}/${version}/wp-private.php"
	export WPP_CAPTURE=1
	if [[ "${version}" == "1.4" || "${version}" == "1.5" ]]; then
		old_file="${LEGACY}/1.6.1/wp-private.php"
		export WPP_CAPTURE=0
	fi
	if run_phase "${slug} old (${version} data)" old "${slug}" "${old_file}"; then
		run_phase "${slug} new (this build over ${version} data)" new "${slug}" ""
		run_phase "${slug} down (1.6.1 files over this build's data)" down "${slug}" "${LEGACY}/1.6.1/wp-private.php"
	fi
done

if [[ $# -eq 0 || "01" == "$1"* ]]; then
	if run_phase "01-fresh-default first2x (site first installed on 2.x)" first2x 01-fresh-default ""; then
		run_phase "01-fresh-default down2x (1.6.1 files over it)" down2x 01-fresh-default "${LEGACY}/1.6.1/wp-private.php"
	fi
fi

# S14 (from R02): the previous 2.x release over each config, then this build. Identical snapshots, no listed change.
# The previous release brings its own Composer autoloader, so it runs under PHPUnit from .cache/phpunit-tools and
# without this build's autoloader (WPP_NO_DEV_AUTOLOAD=1).
PREV_VERSION="${WPP_PREV_VERSION:-2.0.0}"
PREV_FILE="${PWD}/tests/fixtures/legacy/${PREV_VERSION}/wp-private.php"
if [[ -f "${PREV_FILE}" && ( $# -eq 0 || "${WPP_S14:-0}" == "1" ) ]]; then
	if [[ ! -x .cache/phpunit-tools/vendor/bin/phpunit ]]; then
		mkdir -p .cache/phpunit-tools
		printf '%s\n' '{"require":{"phpunit/phpunit":"^9.6"},"config":{"platform":{"php":"8.2.0"}}}' > .cache/phpunit-tools/composer.json
		(cd .cache/phpunit-tools && composer install -q --no-interaction) || { echo "Could not install PHPUnit for S14." >&2; exit 1; }
	fi
	export WPP_PREV_VERSION="${PREV_VERSION}"
	prev_slugs=("${slugs[@]}")
	prev_slugs+=("01-fresh-default")
	for slug in "${prev_slugs[@]}"; do
		unset WP_TESTS_SKIP_INSTALL
		export WPP_CAPTURE=1
		out="$(WPP_NO_DEV_AUTOLOAD=1 WPP_PHASE=prevold WPP_PLUGIN_FILE="${PREV_FILE}" "${PHP}" .cache/phpunit-tools/vendor/phpunit/phpunit/phpunit --bootstrap tests/phpunit/bootstrap-user-configs.php --group upgrade-path --colors=never --filter "/::test_phase with data set \"${slug}\"\$/" 2>&1)"
		status=$?
		export WP_TESTS_SKIP_INSTALL=1
		if [[ ${status} -ne 0 ]] || grep -Eq 'No tests executed|Skipped: [0-9]|Risky: [0-9]|Warnings: [0-9]|incomplete, skipped, or risky' <<<"${out}"; then
			echo "${slug} S14 old (${PREV_VERSION}) ... FAIL"
			sed 's/^/    /' <<<"${out}" | head -"${WPP_FAIL_LINES:-60}"
			failed=$((failed + 1))
			continue
		fi
		echo "${slug} S14 old (${PREV_VERSION}) ... PASS"
		passed=$((passed + 1))
		run_phase "${slug} S14 new (this build over ${PREV_VERSION} data)" prevnew "${slug}" ""
	done
fi

echo
echo "${passed} passed, ${failed} failed in $((SECONDS - start))s."
[[ ${failed} -eq 0 ]]
