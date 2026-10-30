# 🌐 Kirby Google Translate

[![Buy a license: €29.99](https://img.shields.io/badge/Buy_a_license-%E2%82%AC29.99-2ea44f?style=for-the-badge)](https://owebstudio.lemonsqueezy.com/checkout/buy/2a631542-2793-4e73-bdf1-fa33d7b6bce8)

Google Translate integration for Kirby.

This plugin provides [Google Cloud Translation](https://cloud.google.com/translate) V2 API integration for Kirby pages. It makes instant translation and updates the contents of the relevant language.

![google-translate-3](https://user-images.githubusercontent.com/3393422/190928416-e4c832a5-d38b-4670-848b-edbc2a4315c2.gif)

## Installation

1. Download the latest release
2. Unzip downloaded file
3. Copy/paste unzipped folder in your `/site/plugins` folder

## Requirements

- Kirby 4 or 5 (for Kirby 3, use [1.1.0](https://github.com/owebstudio/kirby-google-translate/releases/tag/1.1.0))
- PHP 8.1+
- Google Cloud Translation API KEY

## Usage

After you get the Google API KEY and set it from the config file, enable the translation from the blueprint file of the page you want to use as follows

```yaml
# /site/blueprints/pages/post.yml
title: Post

options:
  translate: true
```

## Google Cloud Translation

[Google Cloud Translation](https://cloud.google.com/translate) is free for up to **500,000 characters** per month. For
detailed pricing, you can
check the official page: https://cloud.google.com/translate/pricing

## Fields & Blocks

Only the following fields and blocks are supported. This is because some fields do not need translation. such as email,
url or date, etc.

Also custom fields are not supported because their data structure is unknown. Therefore this plugin also allows you to define a class that reads and saves the custom data structure of custom fields.

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

| Option         | Type    | Default | Description                                                            |
|:---------------|:--------|:--------|:-----------------------------------------------------------------------|
| apiKey         | string  | null    | Google Cloud Translate Api Key (required)                              |
| fieldTypes     | array   | []      | Determines the field types you want to be translated.                  |
| blockTypes     | array   | []      | Additional block types to translate, next to the core block types.     |
| overwrite      | boolean | false   | If disabled, already translated fields, title and slug are kept.       |
| translateTitle | boolean | true    | When enabled, the title is translated along with the page.             |
| translateSlug  | boolean | true    | When enabled, the slug is translated along with the page.              |

All options optional and the values can be updated in the `config.php` file and prefixed with `owebstudio.google-translate.`.

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

_Field definition can only be done with classes. Field definition with `Closure` is not possible at the moment, but it can be considered in the future._

## License

Google Translate is a commercial plugin. It is free to use on your local computer and on staging sites. A live site needs
a license key: [buy a license](https://owebstudio.lemonsqueezy.com/checkout/buy/2a631542-2793-4e73-bdf1-fa33d7b6bce8)
(€29.99) and activate it in the Panel with the button in the translate dialog, or in the System view (Kirby 5 and newer)
by clicking the license status of the plugin.

Without a license key, the translate dialog shows a notice on a live site and no page is sent to Google. Your website
keeps working.

[oweb studio Commercial License](LICENSE.md). Versions released under the MIT license stay MIT. The current license is
available at https://owebstudio.com/license.

