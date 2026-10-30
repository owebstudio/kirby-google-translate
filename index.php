<?php

use Kirby\Cms\App as Kirby;
use Oweb\GoogleTranslate\License\License;

require_once __DIR__ . '/lib/license/autoload.php';

load([
    'Oweb\\GoogleTranslate\\Fields' => 'src/Fields.php',
    'Oweb\\GoogleTranslate\\Fields\\BlocksField' => 'src/Fields/BlocksField.php',
    'Oweb\\GoogleTranslate\\Fields\\Field' => 'src/Fields/Field.php',
    'Oweb\\GoogleTranslate\\Fields\\LayoutField' => 'src/Fields/LayoutField.php',
    'Oweb\\GoogleTranslate\\Fields\\ListField' => 'src/Fields/ListField.php',
    'Oweb\\GoogleTranslate\\Fields\\StructureField' => 'src/Fields/StructureField.php',
    'Oweb\\GoogleTranslate\\Fields\\TextareaField' => 'src/Fields/TextareaField.php',
    'Oweb\\GoogleTranslate\\Fields\\TextField' => 'src/Fields/TextField.php',
    'Oweb\\GoogleTranslate\\Fields\\WriterField' => 'src/Fields/WriterField.php',
    'Oweb\\GoogleTranslate\\GoogleTranslate' => 'src/GoogleTranslate.php',
    'Oweb\\GoogleTranslate\\Request' => 'src/Request.php',
    'Oweb\\GoogleTranslate\\Translate' => 'src/Translate.php',
], __DIR__);

$license = new License(
    plugin: 'owebstudio/google-translate',
    products: [16242],
    name: 'Google Translate',
    url: 'https://owebstudio.lemonsqueezy.com/checkout/buy/2a631542-2793-4e73-bdf1-fa33d7b6bce8'
);

Kirby::plugin('owebstudio/google-translate', $license->extend([
    'areas' => include __DIR__ . '/config/areas.php',
    'options' => include __DIR__ . '/config/options.php',
    'translations' => include __DIR__ . '/config/translations.php'
]), ...$license->args());
