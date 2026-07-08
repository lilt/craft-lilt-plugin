#!/bin/bash
#
# Runs the integration + functional suites against each CraftCMS version passed as
# an argument. Invoked per matrix cell by `make test-craft-versions` (see
# .github/workflows/craft-versions.yml).
#
# `set -euo pipefail` is important: without it a failed `composer require` reverts
# composer.json/composer.lock and the loop keeps going, so the suite silently runs
# against the previously locked versions instead of the requested one — a false pass.
set -euo pipefail

versions=("$@")

for i in "${versions[@]}"; do
  # Must succeed — if this Craft version can't install, fail the cell loudly.
  composer require craftcms/cms:"$i" -W

  # Optional pin: yiisoft/yii2 2.0.47 is incompatible with Craft < 4.3.5 (which
  # requires ~2.0.45.0). Tolerate the failure so those cells still test on their
  # Craft-compatible yii2 instead of reverting the whole composer state.
  composer require yiisoft/yii2:"2.0.47" -W \
    || echo "::warning::yii2 2.0.47 incompatible with craftcms/cms $i, keeping Craft-resolved yii2"

  composer dump-autoload
  php vendor/bin/codecept build

  php vendor/bin/codecept run integration
  php vendor/bin/codecept run functional
done
