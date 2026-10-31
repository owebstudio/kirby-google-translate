<?php

namespace Oweb\GoogleTranslate;

use Kirby\Cms\App as Kirby;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Exception\PermissionException;
use Kirby\Toolkit\Escape;
use Kirby\Toolkit\HtmlString;
use Kirby\Toolkit\I18n;
use Oweb\GoogleTranslate\License\License;

/**
 * Kirby Google Translate Package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://github.com/owebstudio/kirby-google-translate
 * @copyright oweb studio
 * @license   https://github.com/owebstudio/.github/blob/main/LICENSE.md
 */
class Dialog
{
    public static function load(Request $request): array
    {
        $kirby = Kirby::instance();
        $license = License::for('owebstudio/google-translate')->panel();

        // show the license notice instead of the translation
        if ($license['locked'] === true) {
            return [
                'component' => 'k-form-dialog',
                'props' => [
                    'fields' => [
                        'license' => [
                            'type' => 'info',
                            'theme' => $license['theme'],
                            'text' => static::text($license['message'])
                        ]
                    ],
                    'size' => 'medium',
                    // admins can open the license dialog
                    'submitButton' => $license['dialog'] !== null ? [
                        'dialog' => $license['dialog'],
                        'icon' => 'key',
                        'text' => $license['button'],
                        'type' => 'button'
                    ] : false
                ]
            ];
        }

        $languages = [
            'source' => $kirby->language($request->sourceLanguage())->name(),
            'target' => $kirby->language($request->targetLanguage())->name()
        ];

        $options = [];

        foreach ($request->labels() as $name => $label) {
            $options[] = [
                'value' => $name,
                'text' => static::text($label)
            ];
        }

        return [
            'component' => 'k-form-dialog',
            'props' => [
                'fields' => [
                    'fields' => [
                        'type' => 'checkboxes',
                        'label' => I18n::translate('owebstudio.google-translate.fields'),
                        'help' => static::text(I18n::template('owebstudio.google-translate.fields.help', $languages)),
                        'columns' => 2,
                        'options' => $options,
                        'required' => true
                    ],
                    'overwrite' => [
                        'type' => 'toggle',
                        'label' => I18n::translate('owebstudio.google-translate.overwrite'),
                        'help' => static::text(I18n::template('owebstudio.google-translate.overwrite.help', $languages))
                    ]
                ],
                'size' => 'medium',
                'submitButton' => [
                    'icon' => 'globe',
                    'text' => I18n::translate('owebstudio.google-translate.translate')
                ],
                'value' => [
                    'fields' => array_column($options, 'value'),
                    'overwrite' => $request->overwrite()
                ]
            ]
        ];
    }

    public static function submit(string $path): array
    {
        $kirby = Kirby::instance();
        $license = License::for('owebstudio/google-translate');

        // never call the Google API without a license
        if ($license->isLocked() === true) {
            throw new PermissionException($license->message());
        }

        $fields = (array)$kirby->request()->get('fields', []);

        if ($fields === []) {
            throw new InvalidArgumentException(
                key: 'owebstudio.google-translate.fields',
                fallback: 'Please select at least one field'
            );
        }

        $request = new Request(
            path: $path,
            fields: $fields,
            overwrite: filter_var($kirby->request()->get('overwrite', false), FILTER_VALIDATE_BOOLEAN)
        );

        $page = $request->translate();

        return [
            'redirect' => $page->panel()->url()
        ];
    }

    /**
     * Prepares a plain text for the Panel: the Kirby 6 Panel escapes
     * plain strings, the Kirby 5 Panel renders them as HTML
     */
    protected static function text(string $text): string
    {
        return class_exists(HtmlString::class) === true ? $text : Escape::html($text);
    }
}
