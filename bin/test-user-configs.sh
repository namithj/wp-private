#!/usr/bin/env bash
#
# Run the R01 user-config regression group: configs 01-12 in tests/user-configs/, one after another,
# each in its own PHPUnit process (scenarios S1-S10, S11 data half, S13).
#
#   bin/test-user-configs.sh              run everything
#   bin/test-user-configs.sh 02 05        run only configs whose slug starts with 02 or 05
#   WPP_UPDATE_SNAPSHOTS=1 bin/test-user-configs.sh   regenerate snapshots (never in CI)
#
# WordPress core: unless WP_TESTS_ABSPATH is already set (CI sets it), a throwaway core is installed
# into .cache/wp-core/<version>/ (git-ignored) with bin/install-wp-core.sh, so the real site never
# loads. Choose another version with WP_CORE_VERSION and another PHP binary with PHP. The database
# comes from the WP_TESTS_DB_* variables (defaults in tests/phpunit/wp-tests-config.php).
#
# Config 09 (multisite) runs with WP_TESTS_MULTISITE=1. Config 10 also loads the test-only fixture
# plugin tests/fixtures/coexist-plugin (WPP_LOAD_COEXIST_PLUGIN=1).

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

PHPUNIT=("${PHP}" vendor/phpunit/phpunit/phpunit --bootstrap tests/phpunit/bootstrap-user-configs.php --group user-configs --colors=never)

# The first PHPUnit process installs the test tables; the rest reuse them.
unset WP_TESTS_SKIP_INSTALL
unset WP_TESTS_MULTISITE
unset WPP_LOAD_COEXIST_PLUGIN
unset WPP_PLUGIN_FILE

slugs=()
for file in tests/user-configs/[0-9][0-9]-*.php; do
	slug="$(basename "${file}" .php)"
	if [[ $# -gt 0 ]]; then
		wanted=0
		for prefix in "$@"; do
			[[ "${slug}" == "${prefix}"* ]] && wanted=1
		done
		[[ ${wanted} -eq 0 ]] && continue
	fi
	slugs+=("${slug}")
done

total=${#slugs[@]}
if [[ ${total} -eq 0 ]]; then
	echo "No user config matches: $*" >&2
	exit 1
fi
failed=0
passed=0
start=${SECONDS}
last_mode="single"

index=0
for slug in "${slugs[@]}"; do
	index=$((index + 1))
	mode="single"
	[[ "${slug}" == 09-* ]] && mode="multi"
	if [[ "${mode}" != "${last_mode}" ]]; then
		unset WP_TESTS_SKIP_INSTALL # Switching between single site and multisite reinstalls the tables.
		last_mode="${mode}"
	fi
	env_args=()
	[[ "${mode}" == "multi" ]] && env_args+=(WP_TESTS_MULTISITE=1)
	[[ "${slug}" == 10-* ]] && env_args+=(WPP_LOAD_COEXIST_PLUGIN=1)
	out="$(env "${env_args[@]}" "${PHPUNIT[@]}" --filter "/::test_user_config with data set \"${slug}\"\$/" 2>&1)"
	status=$?
	export WP_TESTS_SKIP_INSTALL=1
	# A run that executed nothing, or skipped or marked tests risky, is not a pass.
	if [[ ${status} -eq 0 ]] && grep -Eq 'No tests executed|Skipped: [0-9]|Risky: [0-9]|Warnings: [0-9]|incomplete, skipped, or risky' <<<"${out}"; then
		status=1
	fi
	label="$(printf '[%02d/%02d] %s' "${index}" "${total}" "${slug}")"
	if [[ ${status} -eq 0 ]]; then
		echo "${label} ... PASS"
		passed=$((passed + 1))
	else
		echo "${label} ... FAIL"
		sed 's/^/    /' <<<"${out}" | head -${WPP_FAIL_LINES:-60}
		failed=$((failed + 1))
	fi
done

echo
echo "${passed} passed, ${failed} failed in $((SECONDS - start))s."
[[ ${failed} -eq 0 ]]
