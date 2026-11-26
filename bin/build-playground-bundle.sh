#!/usr/bin/env bash
#
# Package a plugin ZIP and a blueprint into an offline WordPress Playground bundle.
#
#   bin/build-playground-bundle.sh <plugin-zip> <output-bundle>
#
# The blueprint is .wordpress-org/blueprints/blueprint.json with its plugin step pointed
# at the bundled ZIP, so the demo runs without fetching the plugin.
set -euo pipefail

if [[ $# -ne 2 ]]; then
	echo "Usage: $0 <plugin-zip> <output-bundle>" >&2
	exit 2
fi

PLUGIN_ZIP="$( realpath -m "$1" )"
OUTPUT_BUNDLE="$( realpath -m "$2" )"
ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
BUILD_ROOT="$( mktemp -d )"

cleanup() {
	rm -rf "${BUILD_ROOT}"
}
trap cleanup EXIT

if [[ ! -f "${PLUGIN_ZIP}" ]]; then
	echo "Plugin zip not found: ${PLUGIN_ZIP}" >&2
	exit 1
fi

cd "${ROOT}"
mkdir -p "$( dirname "${OUTPUT_BUNDLE}" )"
cp "${PLUGIN_ZIP}" "${BUILD_ROOT}/wp-private.zip"

node -e '
	const fs = require( "fs" );
	const blueprint = JSON.parse( fs.readFileSync( ".wordpress-org/blueprints/blueprint.json", "utf8" ) );
	let swapped = 0;
	for ( const step of blueprint.steps ) {
		if ( step.step === "installPlugin" ) {
			step.pluginData = { resource: "bundled", path: "/wp-private.zip" };
			step.options = { ...step.options, targetFolderName: "wp-private" };
			swapped++;
		}
	}
	if ( swapped !== 1 ) {
		throw new Error( "Expected exactly one installPlugin step in .wordpress-org/blueprints/blueprint.json." );
	}
	fs.writeFileSync( process.argv[1], JSON.stringify( blueprint, null, 2 ) + "\n" );
' "${BUILD_ROOT}/blueprint.json"

rm -f "${OUTPUT_BUNDLE}"
( cd "${BUILD_ROOT}" && zip -rq "${OUTPUT_BUNDLE}" blueprint.json wp-private.zip )

ZIP_ENTRIES="$( unzip -Z1 "${OUTPUT_BUNDLE}" )"
for file in blueprint.json wp-private.zip; do
	if ! grep -qxF "${file}" <<< "${ZIP_ENTRIES}"; then
		echo "Playground bundle is missing ${file}." >&2
		exit 1
	fi
done

echo "Built ${OUTPUT_BUNDLE}"
