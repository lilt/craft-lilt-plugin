<?php

use craft\test\TestSetup;

ini_set('date.timezone', 'UTC');

// Use the current installation of Craft
define('CRAFT_STORAGE_PATH', __DIR__ . '/_craft/storage');
define('CRAFT_TEMPLATES_PATH', __DIR__ . '/_craft/templates');
define('CRAFT_CONFIG_PATH', __DIR__ . '/_craft/config');
define('CRAFT_MIGRATIONS_PATH', __DIR__ . '/_craft/migrations');
define('CRAFT_TRANSLATIONS_PATH', __DIR__ . '/_craft/translations');
define('CRAFT_TESTS_PATH', __DIR__ . '/_craft/tests');
define('CRAFT_VENDOR_PATH', dirname(__DIR__) . '/vendor');

define('TEST_SUPERTABLE_PLUGIN', false);


$devMode = true;

#include '../vendor/autoload.php';

// Load dotenv?
#if (class_exists('Dotenv\Dotenv') && file_exists('.env')) {
#    Dotenv\Dotenv::create(__DIR__)->load();
#}

TestSetup::configureCraft();

#/**
# * @var Craft $craft
# */
#$craft = TestSetup::warmCraft();


#$test = craft\test\Craft::$instance;
#\craft\test\Craft::$instance->setupDb();

#$plugins = Craft::$app->getUser()->loginByUserId(1);

#$here = true;