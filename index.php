<?php

use Kirby\Cms\App as Kirby;

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

Kirby::plugin('owebstudio/google-translate', [
    'areas' => include __DIR__ . '/config/areas.php',
    'options' => include __DIR__ . '/config/options.php',
    'translations' => include __DIR__ . '/config/translations.php'
]);
