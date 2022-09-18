# 🌐 Kirby Google Translate

Google Translate integration for Kirby 3.

This plugin provides [Google Cloud Translation](https://cloud.google.com/translate) V2 API integration for Kirby 3 pages. It
makes instant translation and updates
the contents of the relevant language.

![google-translate-3](https://user-images.githubusercontent.com/3393422/190928416-e4c832a5-d38b-4670-848b-edbc2a4315c2.gif)

## Installation

1. Download the latest release
2. Unzip downloaded file
3. Copy/paste unzipped folder in your `/site/plugins` folder

## Requirements

- Kirby 3.7+
- PHP 8.0+
- Google Cloud Translation API KEY

## Google Cloud Translation

[Google Cloud Translation](https://cloud.google.com/translate) is free for up to **500,000 characters** per month. For
detailed pricing, you can
check the official page: https://cloud.google.com/translate/pricing

## Fields & Blocks

Only the following fields and blocks are supported. This is because some fields do not need translation. such as email,
url or date, etc.

Also custom fields are not supported because their data structure is unknown. Therefore this plugin also allows you to
define
a class that reads and saves the custom data structure of custom fields.

Supports nested data/form structure, but has not been tested beyond 2 levels.

### Fields

- blocks
- layout
- list
- structure
- textarea
- text
- writer

### Blocks

- heading
- list
- markdown
- quote
- text

## Options

| Option         | Type    | Default | Description                                                |
|:---------------|:--------|:--------|:-----------------------------------------------------------|
| apiKey         | string  | null    | Google Cloud Translate Api Key (required)                  |
| fieldTypes     | array   | []      | Determines the field types you want to be translated.      |
| blockTypes     | array   | []      | Determines the blocks types you want to be translated.     |
| translateTitle | boolean | true    | When enabled, the title is translated along with the page. |
| translateSlug  | boolean | true    | When enabled, the slug is translated along with the page.  |

All options optional and the values can be updated in the `config.php` file and prefixed
with `owebstudio.google-translate.`.

## Sample options

```php
<?php

// /site/config/config.php
return [
    'owebstudio.google-translate' => [
        'apiKey' => 'MY_GOOGLE_API_KEY',
        'blockTypes' => ['my-custom-block-type'],
        'translateTitle' => true,
        'translateSlug' => true
    ]
];
```

## Defining custom field types

- Field should extends `\Oweb\GoogleTranslate\Fields\Field`
- Field have to `value` and `save` methods

```php
<?php

class CustomField extends \Oweb\GoogleTranslate\Fields\Field
{
    public string $format = 'html'; // text or html

    public function value(): string|array
    {
        $value = $this->field()->value();
        $result = $this->translate($value);

        return $this->save($result);
    }

    public function save($value)
    {
        return $value;
    }
}

// /site/config/config.php
return [
    'owebstudio.google-translate' => [
        'apiKey' => 'MY_GOOGLE_API_KEY',
        'fieldTypes' => [
            'custom' => CustomField::class
        ]
    ]
];
```

_Field definition can only be done with classes. Field definition with `Closure` is not possible at the moment, but it
can be considered in the future._

