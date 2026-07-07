#!/usr/bin/env bash
# Build agency (MainWP) and wordpress.org plugin zips from the repo root.
set -euo pipefail

PLUGIN_SLUG="${PLUGIN_SLUG:-smart-purge-for-breeze-cache}"
AGENCY_ZIP="${AGENCY_ZIP:-${PLUGIN_SLUG}.zip}"
WPORG_ZIP="${WPORG_ZIP:-${PLUGIN_SLUG}-wporg.zip}"
BUILD_ROOT="${BUILD_ROOT:-build}"

rm -rf "${BUILD_ROOT}"
mkdir -p "${BUILD_ROOT}"

copy_plugin_tree() {
  local dest="$1"
  local extra_exclude_file="${2:-}"
  local -a tar_excludes=()

  while IFS= read -r line || [[ -n "${line}" ]]; do
    line="${line//$'\r'/}"
    [[ -z "${line}" || "${line}" =~ ^[[:space:]]*# ]] && continue
    tar_excludes+=(--exclude="${line}")
  done < .distignore
  tar_excludes+=(--exclude="${BUILD_ROOT}")

  if [[ -n "${extra_exclude_file}" ]]; then
    while IFS= read -r line || [[ -n "${line}" ]]; do
      line="${line//$'\r'/}"
      [[ -z "${line}" || "${line}" =~ ^[[:space:]]*# ]] && continue
      tar_excludes+=(--exclude="${line}")
    done < "${extra_exclude_file}"
  fi

  mkdir -p "${dest}"
  tar "${tar_excludes[@]}" -c . | tar -x -C "${dest}"
}

echo "Building agency zip: ${AGENCY_ZIP}"
mkdir -p "${BUILD_ROOT}/agency"
copy_plugin_tree "${BUILD_ROOT}/agency/${PLUGIN_SLUG}"
(
  cd "${BUILD_ROOT}/agency"
  zip -r "../../${AGENCY_ZIP}" "${PLUGIN_SLUG}"
)

echo "Building wordpress.org upload zip: ${WPORG_ZIP}"
mkdir -p "${BUILD_ROOT}/wporg"
copy_plugin_tree "${BUILD_ROOT}/wporg/${PLUGIN_SLUG}" ".distignore.wporg"

WPORG_VERSION="${WPORG_VERSION:-1.0.0}"
MAIN_FILE="${BUILD_ROOT}/wporg/${PLUGIN_SLUG}/${PLUGIN_SLUG}.php"
sed -i "s/^ \* Version: .*/ * Version: ${WPORG_VERSION}/" "${MAIN_FILE}"
sed -i "s/define('BSP_VERSION', '[^']*')/define('BSP_VERSION', '${WPORG_VERSION}')/" "${MAIN_FILE}"
if [[ -f readme.wporg.txt ]]; then
  cp readme.wporg.txt "${BUILD_ROOT}/wporg/${PLUGIN_SLUG}/readme.txt"
fi

(
  cd "${BUILD_ROOT}/wporg"
  zip -r "../../${WPORG_ZIP}" "${PLUGIN_SLUG}"
)

WPORG_APPROVED_SLUG="${WPORG_APPROVED_SLUG:-pixelparade-smart-purge-for-breeze-cache}"
WPORG_APPROVED_ZIP="${WPORG_APPROVED_SLUG}-wporg.zip"
echo "Building post-approval wordpress.org zip: ${WPORG_APPROVED_ZIP}"
mkdir -p "${BUILD_ROOT}/wporg-approved"
cp -a "${BUILD_ROOT}/wporg/${PLUGIN_SLUG}" "${BUILD_ROOT}/wporg-approved/${PLUGIN_SLUG}"
bash scripts/apply-wporg-transform.sh "${BUILD_ROOT}/wporg-approved"
(
  cd "${BUILD_ROOT}/wporg-approved"
  zip -r "../../${WPORG_APPROVED_ZIP}" "${WPORG_APPROVED_SLUG}"
)

echo "Done."
echo "  Agency (MainWP / GitHub Releases):     ${AGENCY_ZIP}"
echo "  wordpress.org upload (pending slug):   ${WPORG_ZIP}"
echo "  wordpress.org post-approval slug:      ${WPORG_APPROVED_ZIP}"
