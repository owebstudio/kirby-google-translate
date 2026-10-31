<?php

namespace Oweb\GoogleTranslate;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Cms\Page;
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
    protected Page $page;
    protected string $sourceLanguage;
    protected string $targetLanguage;
    protected array $source;
    protected array $translation;
    protected array|null $fields;
    protected array $except;
    protected bool $overwrite;

    /**
     * @param \Kirby\Cms\Page|string $page The page or its path
     * @param array|null $fields Names of the fields to translate, including
     *                           `title` and `slug`, or `null` for all fields
     * @param bool|null $overwrite If already translated fields are translated
     *                             again, `null` for the `overwrite` option
     * @param array $except Names of the fields not to translate
     */
    public function __construct(
        Page|string $page,
        array|null $fields = null,
        bool|null $overwrite = null,
        array $except = []
    ) {
        $kirby = Kirby::instance();

        $this->page = is_string($page) === true ? Find::page($page) : $page;

        // checks the api key, language, page options and permissions
        static::ensure($this->page);

        $this->sourceLanguage = $kirby->defaultLanguage()->code();
        $this->targetLanguage = $kirby->language()->code();
        $this->source = $this->page->version()->read($this->sourceLanguage()) ?? [];
        $this->translation = $this->readTranslation();
        $this->fields = $fields !== null ? array_map('strtolower', $fields) : null;
        $this->except = array_map('strtolower', $except);
        $this->overwrite = $overwrite ?? ($kirby->option('owebstudio.google-translate.overwrite', false) === true);
    }

    public function translate(): Page
    {
        return static::translateAll([$this])[0];
    }

    /**
     * Translates the pages of all requests and sends the texts
     * of all pages to Google in as few requests as possible
     *
     * @param \Oweb\GoogleTranslate\Request[] $requests
     * @return \Kirby\Cms\Page[]
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
     * Checks the api key, language, page options and permissions
     * before the paid API is called
     *
     * @throws \Kirby\Exception\LogicException
     * @throws \Kirby\Exception\PermissionException
     */
    public static function ensure(Page $page): void
    {
        $kirby = $page->kirby();

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

        if (($page->blueprint()->options()['translate'] ?? false) === false) {
            throw new LogicException(
                key: 'owebstudio.google-translate.disabled',
                fallback: 'The translate option is not enabled in the blueprint'
            );
        }

        if (static::can($page, 'update') === false) {
            throw new PermissionException(
                key: 'page.update.permission',
                data: ['slug' => $page->slug()]
            );
        }
    }

    /**
     * Checks if the current user can translate the page
     */
    public static function isTranslatable(Page $page): bool
    {
        try {
            static::ensure($page);
            return true;
        } catch (LogicException|PermissionException) {
            return false;
        }
    }

    /**
     * Checks if the current user may run the action on the page
     */
    public static function can(Page $page, string $action): bool
    {
        // Kirby 6+
        if (method_exists($page, 'guards') === true) {
            return $page->guards()->isAvailable($action);
        }

        return $page->permissions()->can($action);
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
     * Checks if already translated fields are translated again
     */
    public function overwrite(): bool
    {
        return $this->overwrite;
    }

    public function page(): Page
    {
        return $this->page;
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
            'title' =>
                $kirby->option('owebstudio.google-translate.translateTitle', true) === true &&
                static::can($this->page, 'changeTitle') === true,
            'slug' =>
                $kirby->option('owebstudio.google-translate.translateSlug', true) === true &&
                static::can($this->page, 'changeSlug') === true,
            default => false
        };
    }

    /**
     * Reads the stored content of the target language
     */
    protected function readTranslation(): array
    {
        return $this->page->version()->read($this->targetLanguage()) ?? [];
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
            $title = $this->page()->content($this->sourceLanguage())->get('title')->value();
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
                $title = $this->page()->content($this->sourceLanguage())->get('title')->value();
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
            $this->page = $this->page->changeTitle($title, $this->targetLanguage());
        }

        if (empty($slug) === false) {
            $this->page = $this->page->changeSlug($slug, $this->targetLanguage());
        }
    }

    /**
     * Updates the page with the translated content
     */
    protected function update(): Page
    {
        $content = (new Translate($this))->run();

        // update the translated page
        $this->page = $this->page->update($content, $this->targetLanguage());

        // update title and slug
        $this->translateTitleSlug();

        return $this->page;
    }
}
