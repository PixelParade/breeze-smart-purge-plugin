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

echo "Building wordpress.org zip: ${WPORG_ZIP}"
mkdir -p "${BUILD_ROOT}/wporg"
copy_plugin_tree "${BUILD_ROOT}/wporg/${PLUGIN_SLUG}" ".distignore.wporg"
(
  cd "${BUILD_ROOT}/wporg"
  zip -r "../../${WPORG_ZIP}" "${PLUGIN_SLUG}"
)

echo "Done."
echo "  Agency (MainWP / GitHub Releases): ${AGENCY_ZIP}"
echo "  wordpress.org (SVN):               ${WPORG_ZIP}"
