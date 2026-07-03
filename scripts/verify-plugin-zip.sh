#!/usr/bin/env bash
# Verify agency or wporg plugin zip structure before MainWP / GitHub Release rollout.
# Fails if zip root is wrong, main file missing, or entries contain backslashes (Windows junk-folder bug).
set -euo pipefail

ZIP="${1:-smart-purge-for-breeze-cache.zip}"
SLUG="${2:-smart-purge-for-breeze-cache}"
MAIN_FILE="${SLUG}/${SLUG}.php"

if [[ ! -f "${ZIP}" ]]; then
	echo "Missing zip: ${ZIP}" >&2
	exit 1
fi

echo "Verifying ${ZIP} (slug: ${SLUG})"

if unzip -l "${ZIP}" | grep -q '\\'; then
	echo "FAIL: zip contains backslash path separators — causes undeletable junk folders on Linux hosts." >&2
	unzip -l "${ZIP}" | grep '\\' | head -20 >&2
	exit 1
fi

if ! unzip -l "${ZIP}" | grep -q "${MAIN_FILE}"; then
	echo "FAIL: expected ${MAIN_FILE} in zip root." >&2
	exit 1
fi

# Reject temp-folder roots from broken upgrader extracts (smart-purge-for-breeze-cache-abc123/).
if unzip -l "${ZIP}" | grep -E "${SLUG}-[a-zA-Z0-9]{4,}/" | grep -v "^Archive:"; then
	echo "FAIL: zip contains temp-style folder names (${SLUG}-*)." >&2
	exit 1
fi

echo "OK: ${ZIP}"
