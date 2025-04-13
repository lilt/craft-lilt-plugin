#!/usr/bin/env bash
set -euo pipefail

################################################################################
# This script updates the list of CraftCMS 3.x versions used in the GitHub
# Actions workflow for plugin testing, starting from version 3.7.0.
#
# Why:
# Craft 3.7.0+ is still used in legacy systems, and plugin compatibility
# must be ensured. This script automates that check matrix update.
#
# What it does:
# 1. Downloads all available CraftCMS 3.* versions from Packagist.
# 2. Filters to only include full releases (no beta/RC/dev), and >= 3.7.0.
# 3. Skips known-broken versions listed in IGNORED_VERSIONS.
# 4. Updates the GitHub Actions workflow file with the new list.
################################################################################

WORKFLOW_FILE=".github/workflows/craft-versions.yml"
IGNORED_VERSIONS=(
  # "3.7.40" and "3.7.40.1" contain a FK bug: https://github.com/craftcms/cms/issues/11083
  "3.7.40"
  "3.7.40.1"
)

echo "📦 Fetching CraftCMS versions from Packagist..."

ALL_VERSIONS=$(curl -s "https://repo.packagist.org/p2/craftcms/cms.json" |
  jq -r '.packages["craftcms/cms"][] | .version' |
  grep -E '^3\.[0-9]+(\.[0-9]+){1,2}$' |  # Only full releases: 3.x.x or 3.x.x.x
  sort -V)

FILTERED=()
for version in $ALL_VERSIONS; do
  # Only include >= 3.7.0
  if [[ "$version" < "3.7.0" ]]; then
    continue
  fi

  skip=false
  for ignored in "${IGNORED_VERSIONS[@]}"; do
    if [[ "$version" == "$ignored" ]]; then
      skip=true
      break
    fi
  done
  if ! $skip; then
    FILTERED+=("\"$version\"")
  fi
done

# Format versions into a YAML-compatible string
YAML_CRAFT_MATRIX=$(printf "          %s,\n" "${FILTERED[@]}")

echo "🛠️  Updating matrix in $WORKFLOW_FILE..."

# Use Perl to safely replace the matrix block between known lines
perl -0777 -i -pe "
  s/craft_version: \[.*?\]/craft_version: [\n$YAML_CRAFT_MATRIX        ]/smg
" "$WORKFLOW_FILE"

echo "✅ Done. Updated CraftCMS versions in $WORKFLOW_FILE."
