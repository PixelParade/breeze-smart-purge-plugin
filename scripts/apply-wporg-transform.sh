#!/usr/bin/env bash
# Rename and re-slug the wporg build tree for WordPress.org submission.
set -euo pipefail

BUILD_WPORG_PARENT="${1:?Build wporg parent directory (contains agency slug folder)}"
AGENCY_SLUG="${AGENCY_SLUG:-smart-purge-for-breeze-cache}"
WPORG_SLUG="${WPORG_SLUG:-pixelparade-smart-purge-for-breeze-cache}"
WPORG_VERSION="${WPORG_VERSION:-1.0.0}"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"

SRC_DIR="${BUILD_WPORG_PARENT}/${AGENCY_SLUG}"
DEST_DIR="${BUILD_WPORG_PARENT}/${WPORG_SLUG}"

if [[ ! -d "${SRC_DIR}" ]]; then
	echo "Missing wporg build folder: ${SRC_DIR}" >&2
	exit 1
fi

mv "${SRC_DIR}" "${DEST_DIR}"
mv "${DEST_DIR}/${AGENCY_SLUG}.php" "${DEST_DIR}/${WPORG_SLUG}.php"

find "${DEST_DIR}" -type f \( -name '*.php' -o -name 'readme.txt' \) -print0 | while IFS= read -r -d '' file; do
	sed -i "s/${AGENCY_SLUG}/${WPORG_SLUG}/g" "${file}"
done

sed -i "s/^ \* Version: .*/ * Version: ${WPORG_VERSION}/" "${DEST_DIR}/${WPORG_SLUG}.php"
sed -i "s/define('BSP_VERSION', '[^']*')/define('BSP_VERSION', '${WPORG_VERSION}')/" "${DEST_DIR}/${WPORG_SLUG}.php"

if [[ -f "${REPO_ROOT}/readme.wporg.txt" ]]; then
	cp "${REPO_ROOT}/readme.wporg.txt" "${DEST_DIR}/readme.txt"
fi

echo "Wporg transform complete: ${WPORG_SLUG} (${WPORG_VERSION})"
