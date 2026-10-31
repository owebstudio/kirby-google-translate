<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Panel\Controller\Dropdown\PageSettingsDropdownController;
use Oweb\GoogleTranslate\Dialog;
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
                        if (class_exists(PageSettingsDropdownController::class) === true) {
                            // Kirby 6+
                            $dropdown = (new PageSettingsDropdownController($page))->load();
                        } else {
                            $dropdown = $page->panel()->dropdown();
                        }

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
                        if (Request::can($page, 'update') === false) {
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
                    // checks the api key, language, page options and permissions
                    'load' => fn (string $path) => Dialog::load(new Request($path)),
                    'submit' => fn (string $path) => Dialog::submit($path)
                ]
            ]
        ];
    },
];
