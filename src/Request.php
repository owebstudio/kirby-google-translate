<?php

namespace Oweb\GoogleTranslate;

use InvalidArgumentException;
use Kirby\Cms\App as Kirby;
use Kirby\Cms\Find;
use Kirby\Cms\Page;
use Kirby\Form\Form;
use Google\Cloud\Translate\V2\TranslateClient;
use Kirby\Toolkit\Str;

/**
 * Kirby Google Translate Package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class Request
{
    protected Page $page;
    protected TranslateClient $client;
    protected string $sourceLanguage;
    protected string $targetLanguage;

    public function __construct(string $path)
    {
        $this->page = Find::page($path);
        $this->sourceLanguage = Kirby::instance()->defaultLanguage()->code();
        $this->targetLanguage = Kirby::instance()->language()->code();
    }

    public function translate(): Page
    {
        $translate = new Translate($this);
        $content = $translate->run();

        // update the translated page
        $this->page = $this->page->update($content, $this->targetLanguage());

        // update title and slug
        $this->translateTitleSlug();

        return $this->page;
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

    protected function translateTitleSlug(): void
    {
        if (Kirby::instance()->option('owebstudio.google-translate.translateTitle', true) === true) {
            $translatedTitle = GoogleTranslate::translate($this->page()->title()->value(), $this->targetLanguage());

            if (empty($translatedTitle) === false) {
                $this->page = $this->page->changeTitle($translatedTitle, $this->targetLanguage());

                if (Kirby::instance()->option('owebstudio.google-translate.translateSlug', true) === true) {
                    $this->page = $this->page->changeSlug(Str::slug($translatedTitle), $this->targetLanguage());
                }
            }
        }

    }
}
