<?php

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\User;
use Kirby\Panel\Controller\Dropdown\PageSettingsDropdownController;
use Kirby\Panel\Controller\Dropdown\UserSettingsDropdownController;
use Oweb\GoogleTranslate\Dialog;
use Oweb\GoogleTranslate\Request;

// appends the translate option to the dropdown of a page or user
$dropdown = function (array $options, Page|User $model): array {
    if ($button = Dialog::button($model)) {
        $options[] = '-';
        $options[] = $button;
    }

    return $options;
};

$user = [
    'options' => function (string $id) use ($dropdown) {
        // find the right user for the dropdown
        $user = Find::user($id);

        // load the core dropdown definition
        if (class_exists(UserSettingsDropdownController::class) === true) {
            // Kirby 6+
            $options = (new UserSettingsDropdownController($user))->load();
        } else {
            $options = $user->panel()->dropdown();
        }

        return $dropdown($options, $user);
    }
];

// checks the api key, language, blueprint options and permissions
$dialog = fn (Closure $model) => [
    'load' => fn (string|null $id = null) => Dialog::load(new Request($model($id))),
    'submit' => fn (string|null $id = null) => Dialog::submit($model($id))
];

return [
    'site' => function (Kirby $kirby) use ($dialog, $dropdown) {
        return [
            'buttons' => [
                // view button for the site, pages and users, e.g.
                // `buttons: [open, preview, google-translate, languages]` in `site.yml`
                'google-translate' => function (mixed $model = null) {
                    if ($model instanceof Page || $model instanceof Site || $model instanceof User) {
                        return Dialog::button($model);
                    }

                    return null;
                }
            ],
            'dropdowns' => [
                'page' => [
                    'pattern' => 'pages/(:any)',
                    'options' => function (string $path) use ($dropdown) {
                        // find the right page for the dropdown
                        $page = Find::page($path);

                        // load the core dropdown definition
                        if (class_exists(PageSettingsDropdownController::class) === true) {
                            // Kirby 6+
                            $options = (new PageSettingsDropdownController($page))->load();
                        } else {
                            $options = $page->panel()->dropdown();
                        }

                        return $dropdown($options, $page);
                    }
                ]
            ],
            'dialogs' => [
                'page.translate' => [
                    'pattern' => 'pages/(:any)/translate',
                    ...$dialog(fn (string $path) => Find::page($path))
                ],
                'site.translate' => [
                    'pattern' => 'site/translate',
                    ...$dialog(fn () => $kirby->site())
                ]
            ]
        ];
    },
    'users' => function () use ($dialog, $user) {
        return [
            'dropdowns' => [
                'user' => [
                    'pattern' => 'users/(:any)',
                    ...$user
                ]
            ],
            'dialogs' => [
                'user.translate' => [
                    'pattern' => 'users/(:any)/translate',
                    ...$dialog(fn (string $id) => Find::user($id))
                ]
            ]
        ];
    },
    'account' => function () use ($dialog, $user) {
        return [
            'dropdowns' => [
                'account' => [
                    'pattern' => '(account)',
                    ...$user
                ]
            ],
            'dialogs' => [
                'account.translate' => [
                    'pattern' => '(account)/translate',
                    ...$dialog(fn (string $id) => Find::user($id))
                ]
            ]
        ];
    },
];
