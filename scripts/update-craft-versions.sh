#!/usr/bin/env bash
set -euo pipefail

################################################################################
# This script updates the list of CraftCMS 4.x versions used in the GitHub
# Actions workflow for plugin testing.
#
# Why:
# Craft frequently releases new 4.x versions, and we want to automatically
# test our plugin against all of them, while skipping known-broken versions.
#
# What it does:
# 1. Downloads all available CraftCMS 4.* versions from Packagist.
# 2. Filters to only include full releases (no beta/RC/dev).
# 3. Skips known-broken versions listed in IGNORED_VERSIONS.
# 4. Updates the GitHub Actions workflow file with the new list.
################################################################################

WORKFLOW_FILE=".github/workflows/craft-versions.yml"
IGNORED_VERSIONS=(
  # Investigation:
  # https://github.com/craftcms/cms/issues/11083
  #"4.0.0" and  "4.0.0.1", contains FK bug, it tries to update field on draft (after apply draft removed, so it fails)
  "4.0.0"
  "4.0.0.1"
   # Seems like bug in CraftCMS https://github.com/lilt/craft-lilt-plugin/actions/runs/8605507218/job/23582189269?pr=146
  "4.5.0"
  "4.5.1"
   # Bug in CraftCMS https://github.com/lilt/craft-lilt-plugin/actions/runs/8605507218/job/23582193181?pr=146
  "4.7.2"
)

echo "📦 Fetching CraftCMS versions from Packagist..."

ALL_VERSIONS=$(curl -s "https://repo.packagist.org/p2/craftcms/cms.json" |
  jq -r '.packages["craftcms/cms"][] | .version' |
  grep -E '^4\.[0-9]+(\.[0-9]+){1,2}$' |  # Only 4.x.x or 4.x.x.x
  sort -V)

FILTERED=()
for version in $ALL_VERSIONS; do
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
