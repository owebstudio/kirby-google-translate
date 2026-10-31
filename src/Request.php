<?php

namespace Oweb\GoogleTranslate;

use InvalidArgumentException;
use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Cms\Page;
use Kirby\Exception\PermissionException;
use Kirby\Form\Form;
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
    protected array $translation;

    public function __construct(string $path)
    {
        $kirby = Kirby::instance();

        // check translate api key
        if (empty($kirby->option('owebstudio.google-translate.apiKey')) === true) {
            throw new InvalidArgumentException('Google Translation API KEY not defined yet!');
        }

        // only secondary languages can be translated
        if ($kirby->multilang() === false || $kirby->language()->isDefault() === true) {
            throw new InvalidArgumentException('Google Translation is only available for secondary languages!');
        }

        $this->page = Find::page($path);

        if (($this->page->blueprint()->options()['translate'] ?? false) === false) {
            throw new InvalidArgumentException('Google Translation setting disabled for the page!');
        }

        // check the permission before the paid API is called
        if (static::can($this->page, 'update') === false) {
            throw new PermissionException(
                key: 'page.update.permission',
                data: ['slug' => $this->page->slug()]
            );
        }

        $this->sourceLanguage = $kirby->defaultLanguage()->code();
        $this->targetLanguage = $kirby->language()->code();
        $this->translation = $this->readTranslation();
    }

    public function translate(): Page
    {
        $translate = new Translate($this);

        // collect all texts first to translate them in as few requests as possible
        GoogleTranslate::prefetch(function () use ($translate) {
            $translate->run();
            $this->translateTitle();
        });

        $content = $translate->run();

        // update the translated page
        $this->page = $this->page->update($content, $this->targetLanguage());

        // update title and slug
        $this->translateTitleSlug();

        return $this->page;
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

    public function page(): Page
    {
        return $this->page;
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
     * before the translation, without the fallback to the default language
     */
    public function translated(string $field): bool
    {
        return empty($this->translation[strtolower($field)] ?? null) === false;
    }

    /**
     * Reads the stored content of the target language
     */
    protected function readTranslation(): array
    {
        return $this->page->version()->read($this->targetLanguage()) ?? [];
    }

    /**
     * Returns the translated title,
     * or `null` if the title is not translated
     */
    protected function translateTitle(): string|null
    {
        $overwrite = Kirby::instance()->option('owebstudio.google-translate.overwrite', false);

        if (
            Kirby::instance()->option('owebstudio.google-translate.translateTitle', true) === true &&
            ($overwrite === true || $this->translated('title') === false) &&
            static::can($this->page, 'changeTitle') === true
        ) {
            $title = $this->page()->content($this->sourceLanguage())->get('title')->value();
            return GoogleTranslate::translate($title, $this->targetLanguage());
        }

        return null;
    }

    protected function translateTitleSlug(): void
    {
        $overwrite = Kirby::instance()->option('owebstudio.google-translate.overwrite', false);
        $translatedTitle = $this->translateTitle();

        if (empty($translatedTitle) === false) {
            $this->page = $this->page->changeTitle($translatedTitle, $this->targetLanguage());

            if (
                Kirby::instance()->option('owebstudio.google-translate.translateSlug', true) === true &&
                ($overwrite === true || $this->translated('slug') === false) &&
                static::can($this->page, 'changeSlug') === true
            ) {
                $this->page = $this->page->changeSlug(Str::slug($translatedTitle), $this->targetLanguage());
            }
        }
    }
}
