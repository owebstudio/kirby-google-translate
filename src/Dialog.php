<?php

namespace Oweb\GoogleTranslate;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
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

        $fields = [
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
        ];

        $subpages = static::subpages($request->page())->count();

        if ($subpages > 0) {
            $fields['subpages'] = [
                'type' => 'toggle',
                'label' => I18n::translate('owebstudio.google-translate.subpages'),
                'text' => I18n::template('owebstudio.google-translate.subpages.text', ['count' => $subpages]),
                'help' => static::text(I18n::translate('owebstudio.google-translate.subpages.help'))
            ];
        }

        return [
            'component' => 'k-form-dialog',
            'props' => [
                'fields' => $fields,
                'size' => 'medium',
                'submitButton' => [
                    'icon' => 'globe',
                    'text' => I18n::translate('owebstudio.google-translate.translate')
                ],
                'value' => [
                    'fields' => array_column($options, 'value'),
                    'overwrite' => $request->overwrite(),
                    'subpages' => false
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

        $overwrite = filter_var($kirby->request()->get('overwrite', false), FILTER_VALIDATE_BOOLEAN);

        $requests = [
            new Request(
                page: $path,
                fields: $fields,
                overwrite: $overwrite
            )
        ];

        if (filter_var($kirby->request()->get('subpages', false), FILTER_VALIDATE_BOOLEAN) === true) {
            // subpages are translated with all their fields, the title
            // and slug only if they are translated for the page as well
            $except = array_diff(['title', 'slug'], array_map('strtolower', $fields));

            foreach (static::subpages($requests[0]->page()) as $subpage) {
                $requests[] = new Request(
                    page: $subpage,
                    overwrite: $overwrite,
                    except: $except
                );
            }
        }

        $pages = Request::translateAll($requests);
        $response = [
            'redirect' => $pages[0]->panel()->url()
        ];

        if (count($pages) > 1) {
            $response['message'] = I18n::template('owebstudio.google-translate.translated', ['count' => count($pages)]);
        }

        return $response;
    }

    /**
     * Returns all subpages and drafts below the page
     * that the current user can translate
     */
    public static function subpages(Page $page): Pages
    {
        return $page->index(true)->filter(fn (Page $subpage) => Request::isTranslatable($subpage));
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
