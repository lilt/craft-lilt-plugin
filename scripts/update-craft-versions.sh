#!/usr/bin/env bash
set -euo pipefail

WORKFLOW_FILE=".github/workflows/tests.yml"
IGNORED_VERSIONS=(
  "4.0.0"
  "4.5.0"
  "4.5.1"
  "4.7.2"
)

echo "Fetching CraftCMS versions from Packagist..."

# Get all CraftCMS 4.x versions from Packagist
ALL_VERSIONS=$(curl -s "https://repo.packagist.org/p2/craftcms/cms.json" |
  jq -r '.packages["craftcms/cms"][] | .version' |
  grep -E '^4\.' |
  sort -V)

# Filter out ignored versions
FILTERED_VERSIONS=()
for version in $ALL_VERSIONS; do
  skip=false
  for ignored in "${IGNORED_VERSIONS[@]}"; do
    if [[ "$version" == "$ignored" ]]; then
      skip=true
      break
    fi
  done
  if [ "$skip" = false ]; then
    FILTERED_VERSIONS+=("\"$version\"")
  fi
done

# Convert to YAML array format (indented properly)
YAML_VERSIONS=$(printf "          %s\n" "${FILTERED_VERSIONS[@]}")

# Replace matrix in GitHub Action workflow file
echo "Updating $WORKFLOW_FILE..."

awk -v new_versions="$YAML_VERSIONS" '
  BEGIN { inside_matrix = 0 }
  /matrix:/ { inside_matrix = 1; print; next }
  inside_matrix && /craft_version:/ {
    print "        craft_version: ["
    print new_versions
    print "        ]"
    # Skip the rest of the matrix versions
    skip = 1
    next
  }
  skip && /^\s*\]/ { skip = 0; next }
  skip { next }
  { print }
' "$WORKFLOW_FILE" > "${WORKFLOW_FILE}.tmp"

mv "${WORKFLOW_FILE}.tmp" "$WORKFLOW_FILE"

echo "✅ Done. Updated CraftCMS versions in $WORKFLOW_FILE."
