#!/bin/bash

## declare an array variable
declare -a arr=(
                  "3.7.0"
                  "3.7.1"
                  "3.7.2"
                  "3.7.3.1"
                  "3.7.3.2"
                  "3.7.3"
                  "3.7.4"
                  "3.7.5"
                  "3.7.6"
                  "3.7.8"
                  "3.7.9"
                  "3.7.10"
                  "3.7.11"
                  "3.7.12"
                  "3.7.13"
                  "3.7.14"
                  "3.7.15"
                  "3.7.16"
                  "3.7.17"
                  "3.7.17.1"
                  "3.7.17.2"
                  "3.7.18"
                  "3.7.18.1"
                  "3.7.18.2"
                  "3.7.19"
                  "3.7.19.1"
                  "3.7.20"
                  "3.7.21"
                  "3.7.22"
                  "3.7.23"
                  "3.7.24"
                  "3.7.25"
                  "3.7.25.1"
                  "3.7.26"
                  "3.7.27"
                  "3.7.27.1"
                  "3.7.27.2"
                  "3.7.28"
                  "3.7.29"
                  "3.7.30"
                  "3.7.30.1"
                  "3.7.31"
                  "3.7.32"
                  "3.7.33"
                  "3.7.34"
                  "3.7.35"
                  "3.7.36"
                  "3.7.37"
                  "3.7.38"
                  "3.7.39"
                  "3.7.40"
                  "3.7.40.1"
                  "3.7.41"
                  "3.7.42"
                  "3.7.43"
                  "3.7.44"
                  )

## now loop through the above array
for i in "${arr[@]}"
do
   #rm -rf vendor
   #rm -rf composer.lock
   composer require craftcms/cms:"$i" -W
   composer dump-autoload
   php vendor/bin/codecept build
   php vendor/bin/codecept run integration
   if [ $? -ne 0 ]; then
         break
   fi
   php vendor/bin/codecept run functional
    if [ $? -ne 0 ]; then
          break
    fi
done