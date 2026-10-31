<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Exception\PermissionException;
use Kirby\Toolkit\Escape;
use Kirby\Toolkit\HtmlString;
use Kirby\Toolkit\I18n;
use Oweb\GoogleTranslate\License\License;
use Oweb\GoogleTranslate\Request;

return [
    'site' => function (Kirby $kirby) {
        // the Kirby 6 Panel escapes all strings that are not marked as trusted HTML
        $trusted = fn (string $html) => class_exists(HtmlString::class) === true ? new HtmlString($html) : $html;

        return [
            'dropdowns' => [
                'page' => [
                    'pattern' => 'pages/(:any)',
                    'options' => function (string $path) use ($kirby) {
                        // find the right page for the dropdown
                        $page = Find::page($path);

                        // load the core dropdown definition
                        $dropdown = $page->panel()->dropdown();

                        // check translate api key
                        if (empty($kirby->option('owebstudio.google-translate.apiKey')) === true) {
                            return $dropdown;
                        }

                        // don't load if multilang disabled
                        // or default language is active
                        if (
                            $kirby->multilang() === false ||
                            $kirby->language() === $kirby->defaultLanguage()
                        ) {
                            return $dropdown;
                        }

                        // get page options
                        $translate = $page->blueprint()->options()['translate'] ?? false;

                        // translate option disabled
                        if ($translate === false) {
                            return $dropdown;
                        }

                        // user is not allowed to update the page
                        if ($page->permissions()->can('update') === false) {
                            return $dropdown;
                        }

                        // append a separator
                        $dropdown[] = '-';

                        // append a new option
                        $dropdown[] = [
                            'icon' => 'globe',
                            'text' => t('owebstudio.google-translate.translate'),
                            'dialog' => $page->panel()->url(true) . '/translate'
                        ];

                        return $dropdown;
                    }
                ]
            ],
            'dialogs' => [
                'page.translate' => [
                    'pattern' => 'pages/(:any)/translate',
                    'load' => function (string $path) use ($trusted) {
                        // checks the api key, language, page options and permissions
                        new Request($path);

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
                                            'text' => $trusted(Escape::html($license['message']))
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

                        return [
                            'component' => 'k-text-dialog',
                            'props' => [
                                'size' => 'medium',
                                'submitButton' => I18n::translate('confirm'),
                                'text' => $trusted(t('owebstudio.google-translate.info') . "<br><br>" . t('owebstudio.google-translate.confirm')),
                                'theme' => 'negative'
                            ]
                        ];
                    },
                    'submit' => function (string $path) use ($kirby) {
                        $license = License::for('owebstudio/google-translate');

                        // never call the Google API without a license
                        if ($license->isLocked() === true) {
                            throw new PermissionException($license->message());
                        }

                        $request = new Request($path);
                        $page = $request->translate();

                        return [
                            'redirect' => $page->panel()->url()
                        ];
                    }
                ]
            ]
        ];
    },
];
