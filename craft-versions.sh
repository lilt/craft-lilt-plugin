#!/bin/bash

versions=("$@")

for i in "${versions[@]}"; do
  composer require craftcms/cms:"$i" -W
  # Best-effort bump: newer Craft (>= 4.3.4) allows yii2 2.0.47; older versions
  # pin yii2 to ~2.0.45/~2.0.46, where this require is incompatible. Don't abort
  # the run on those — Craft's own constraint already resolves a compatible yii2.
  composer require yiisoft/yii2:"2.0.47" -W || true
  composer dump-autoload
  php vendor/bin/codecept build

  if ! php vendor/bin/codecept run integration; then
    exit 1
  fi

  if ! php vendor/bin/codecept run functional; then
    exit 1
  fi
done
