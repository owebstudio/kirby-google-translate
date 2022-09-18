<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Toolkit\I18n;
use Oweb\GoogleTranslate\Request;

return [
    'site' => function (Kirby $kirby) {
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
                    'load' => function (string $path) use ($kirby) {
                        // check translate api key
                        if (empty($kirby->option('owebstudio.google-translate.apiKey')) === true) {
                            throw new InvalidArgumentException('Google Translation API KEY not defined yet!');
                        }

                        $page = Find::page($path);
                        $translate = $page->blueprint()->options()['translate'] ?? false;

                        if ($translate === false) {
                            throw new InvalidArgumentException('Google Translation setting disabled for the page!');
                        }

                        return [
                            'component' => 'k-text-dialog',
                            'props' => [
                                'submitButton' => I18n::translate('confirm'),
                                'text' => t('owebstudio.google-translate.confirm'),
                                'theme' => 'negative'
                            ]
                        ];
                    },
                    'submit' => function (string $path) use ($kirby) {
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
