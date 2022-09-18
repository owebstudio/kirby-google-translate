<?php

use Kirby\Cms\App as Kirby;

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('owebstudio/google-translate', [
    'areas' => include __DIR__ . '/config/areas.php',
    'options' => include __DIR__ . '/config/options.php',
    'translations' => include __DIR__ . '/config/translations.php'
]);
