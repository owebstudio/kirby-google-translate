<?php

namespace Oweb\GoogleTranslate\Fields;

use Kirby\Cms\Page;
use Oweb\GoogleTranslate\GoogleTranslate;
use Oweb\GoogleTranslate\Request;
use Kirby\Form\Field as KirbyField;
use Kirby\Form\FieldClass;
use Kirby\Form\Form;

/**
 * Field package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://github.com/owebstudio/kirby-google-translate
 * @copyright oweb studio
 * @license   https://github.com/owebstudio/.github/blob/main/LICENSE.md
 */
abstract class Field
{
    public string $format = 'text';
    private Request $request;
    private KirbyField|FieldClass $field;

    public function __construct(KirbyField|FieldClass $field, Request $request)
    {
        $this->request = $request;
        $this->field = $field;
    }

    abstract function value(): string|array;

    abstract function save($value);

    public function field(): KirbyField|FieldClass
    {
        return $this->field;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function page(): Page
    {
        return $this->request->page();
    }

    public function sourceLanguage(): string
    {
        return $this->request->sourceLanguage();
    }

    public function targetLanguage(): string
    {
        return $this->request->targetLanguage();
    }

    public function translate(string $value): string
    {
        return GoogleTranslate::translate($value, $this->targetLanguage(), $this->format);
    }

    public function translateBatch(array $strings): array
    {
        return GoogleTranslate::translateBatch($strings, $this->targetLanguage(), $this->format);
    }

    /**
     * Fills a form of nested fields with the given values
     */
    public function fill(Form $form, array $values): Form
    {
        return $form->fill(input: $values);
    }
}
