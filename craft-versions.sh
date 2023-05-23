#!/bin/bash

versions=("$@")

for i in "${versions[@]}"; do
  composer require craftcms/cms:"$i" -W
  composer require yiisoft/yii2:"2.0.47" -W
  composer dump-autoload
  php vendor/bin/codecept build

  if ! php vendor/bin/codecept run integration; then
    exit 1
  fi

  if ! php vendor/bin/codecept run functional; then
    exit 1
  fi
done
