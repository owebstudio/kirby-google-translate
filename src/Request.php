<?php

namespace Oweb\GoogleTranslate;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Cms\User;
use Kirby\Data\Data;
use Kirby\Exception\LogicException;
use Kirby\Exception\PermissionException;
use Kirby\Form\Form;
use Kirby\Toolkit\I18n;
use Kirby\Toolkit\Str;

/**
 * Kirby Google Translate Package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://github.com/owebstudio/kirby-google-translate
 * @copyright oweb studio
 * @license   https://github.com/owebstudio/.github/blob/main/LICENSE.md
 */
class Request
{
    protected Page|Site|User $model;
    protected string $sourceLanguage;
    protected string $targetLanguage;
    protected array $source;
    protected array $translation;
    protected array|null $fields;
    protected array $except;
    protected bool $overwrite;

    /**
     * @param \Kirby\Cms\Page|\Kirby\Cms\Site|\Kirby\Cms\User|string $model
     *        The page, site or user, or the path of a page
     * @param array|null $fields Names of the fields to translate, including
     *                           `title` and `slug`, or `null` for all fields
     * @param bool|null $overwrite If already translated fields are translated
     *                             again, `null` for the `overwrite` option
     * @param array $except Names of the fields not to translate
     */
    public function __construct(
        Page|Site|User|string $model,
        array|null $fields = null,
        bool|null $overwrite = null,
        array $except = []
    ) {
        $kirby = Kirby::instance();

        $this->model = is_string($model) === true ? Find::page($model) : $model;

        // checks the api key, language, blueprint options and permissions
        static::ensure($this->model);

        $this->sourceLanguage = $kirby->defaultLanguage()->code();
        $this->targetLanguage = $kirby->language()->code();
        $this->source = $this->model->version()->read($this->sourceLanguage()) ?? [];
        $this->translation = $this->readTranslation();
        $this->fields = $fields !== null ? array_map('strtolower', $fields) : null;
        $this->except = array_map('strtolower', $except);
        $this->overwrite = $overwrite ?? ($kirby->option('owebstudio.google-translate.overwrite', false) === true);
    }

    public function translate(): Page|Site|User
    {
        return static::translateAll([$this])[0];
    }

    /**
     * Translates the content of all requests and sends the texts
     * of all of them to Google in as few requests as possible
     *
     * @param \Oweb\GoogleTranslate\Request[] $requests
     * @return array<\Kirby\Cms\Page|\Kirby\Cms\Site|\Kirby\Cms\User>
     */
    public static function translateAll(array $requests): array
    {
        // collect all texts first
        GoogleTranslate::prefetch(function () use ($requests) {
            foreach ($requests as $request) {
                (new Translate($request))->run();
                $request->translateSlug($request->translateTitle());
            }
        });

        return array_map(fn (Request $request) => $request->update(), $requests);
    }

    /**
     * Checks the api key, language, blueprint options
     * and permissions before the paid API is called
     *
     * @throws \Kirby\Exception\LogicException
     * @throws \Kirby\Exception\PermissionException
     */
    public static function ensure(Page|Site|User $model): void
    {
        $kirby = $model->kirby();

        // check translate api key
        if (empty($kirby->option('owebstudio.google-translate.apiKey')) === true) {
            throw new LogicException(
                key: 'owebstudio.google-translate.apiKey',
                fallback: 'The Google Translate API key is not set'
            );
        }

        // only secondary languages can be translated
        if ($kirby->multilang() === false || $kirby->language()->isDefault() === true) {
            throw new LogicException(
                key: 'owebstudio.google-translate.language',
                fallback: 'Only secondary languages can be translated'
            );
        }

        if (($model->blueprint()->options()['translate'] ?? false) === false) {
            throw new LogicException(
                key: 'owebstudio.google-translate.disabled',
                fallback: 'The translate option is not enabled in the blueprint'
            );
        }

        if (static::can($model, 'update') === false) {
            throw match (true) {
                $model instanceof Page => new PermissionException(
                    key: 'page.update.permission',
                    data: ['slug' => $model->slug()]
                ),
                $model instanceof Site => new PermissionException(
                    key: 'site.update.permission'
                ),
                $model instanceof User => new PermissionException(
                    key: 'user.update.permission',
                    data: ['name' => $model->username()]
                )
            };
        }
    }

    /**
     * Checks if the current user can translate the page, site or user
     */
    public static function isTranslatable(Page|Site|User $model): bool
    {
        try {
            static::ensure($model);
            return true;
        } catch (LogicException|PermissionException) {
            return false;
        }
    }

    /**
     * Checks if the current user may run the action on the page, site or user
     */
    public static function can(Page|Site|User $model, string $action): bool
    {
        // Kirby 6+
        if (method_exists($model, 'guards') === true) {
            return $model->guards()->isAvailable($action);
        }

        return $model->permissions()->can($action);
    }

    /**
     * Returns the labels of all fields that can be translated,
     * including the title and slug, by field name
     */
    public function labels(): array
    {
        $labels = [];

        if ($this->allows('title') === true) {
            $labels['title'] = I18n::translate('title');
        }

        if ($this->allows('slug') === true) {
            $labels['slug'] = I18n::translate('slug');
        }

        foreach ((new Translate($this))->form()->fields() as $field) {
            if (Translate::accepts($field) === true) {
                $labels[$field->name()] = $field->label() ?? Str::ucfirst($field->name());
            }
        }

        return $labels;
    }

    /**
     * Returns the page, site or user
     */
    public function model(): Page|Site|User
    {
        return $this->model;
    }

    /**
     * Checks if already translated fields are translated again
     */
    public function overwrite(): bool
    {
        return $this->overwrite;
    }

    /**
     * Returns the page, or `null` for the site and users
     */
    public function page(): Page|null
    {
        return $this->model instanceof Page ? $this->model : null;
    }

    /**
     * Checks if the field is one of the fields to translate
     */
    public function selected(string $field): bool
    {
        $field = strtolower($field);

        return
            ($this->fields === null || in_array($field, $this->fields, true) === true) &&
            in_array($field, $this->except, true) === false;
    }

    public function sourceLanguage(): string
    {
        return $this->sourceLanguage ?? 'en';
    }

    public function targetLanguage(): string
    {
        return $this->targetLanguage;
    }

    /**
     * Checks if the target language had its own value for the field
     * before the translation, without the fallback to the default language.
     * Kirby stores the values of all fields when a translation is updated,
     * so a value that is the same as the stored value of the source language
     * is a copy and not a translation.
     *
     * @param mixed $source Stored value of the field in the source language,
     *                      read from the content file if not given
     */
    public function translated(string $field, mixed $source = null): bool
    {
        $field = strtolower($field);
        $value = $this->translation[$field] ?? null;

        if (empty($value) === true) {
            return false;
        }

        $source ??= $this->source[$field] ?? null;

        // compare it the way Kirby stores the value in the content file
        $source = match (true) {
            is_array($source) => Data::encode($source, 'yaml'),
            is_bool($source) => $source === true ? 'true' : 'false',
            default => (string)$source
        };

        return $value !== trim($source);
    }

    /**
     * Checks if the plugin options and the permissions
     * of the user allow to translate the title or slug
     */
    protected function allows(string $field): bool
    {
        $kirby = Kirby::instance();

        return match ($field) {
            // users have no title
            'title' =>
                $this->model instanceof User === false &&
                $kirby->option('owebstudio.google-translate.translateTitle', true) === true &&
                static::can($this->model, 'changeTitle') === true,
            'slug' =>
                $this->model instanceof Page &&
                $kirby->option('owebstudio.google-translate.translateSlug', true) === true &&
                static::can($this->model, 'changeSlug') === true,
            default => false
        };
    }

    /**
     * Reads the stored content of the target language
     */
    protected function readTranslation(): array
    {
        return $this->model->version()->read($this->targetLanguage()) ?? [];
    }

    /**
     * Checks if the title or slug is translated in this request
     */
    protected function translates(string $field): bool
    {
        return
            $this->selected($field) === true &&
            ($this->overwrite === true || $this->translated($field) === false) &&
            $this->allows($field) === true;
    }

    /**
     * Returns the translated title,
     * or `null` if the title is not translated
     */
    protected function translateTitle(): string|null
    {
        if ($this->translates('title') === true) {
            $title = $this->model->content($this->sourceLanguage())->get('title')->value();
            return GoogleTranslate::translate($title, $this->targetLanguage());
        }

        return null;
    }

    /**
     * Returns the slug for the new or the already translated title,
     * or `null` if the slug is not translated
     */
    protected function translateSlug(string|null $title): string|null
    {
        if ($this->translates('slug') === true) {
            if (empty($title) === true && $this->translated('title') === true) {
                $title = $this->translation['title'];
            }

            if (empty($title) === true) {
                $title = $this->model->content($this->sourceLanguage())->get('title')->value();
                $title = GoogleTranslate::translate($title, $this->targetLanguage());
            }

            return Str::slug($title);
        }

        return null;
    }

    protected function translateTitleSlug(): void
    {
        $title = $this->translateTitle();
        $slug = $this->translateSlug($title);

        if (empty($title) === false) {
            $this->model = $this->model->changeTitle($title, $this->targetLanguage());
        }

        if (empty($slug) === false) {
            $this->model = $this->model->changeSlug($slug, $this->targetLanguage());
        }
    }

    /**
     * Updates the page, site or user with the translated content
     */
    protected function update(): Page|Site|User
    {
        $content = (new Translate($this))->run();

        // update the translated content
        $this->model = $this->model->update($content, $this->targetLanguage());

        // update title and slug
        $this->translateTitleSlug();

        return $this->model;
    }
}
