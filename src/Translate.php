<?php

namespace Oweb\GoogleTranslate;

use Kirby\Cms\Page;
use Kirby\Form\Field;
use Kirby\Form\FieldClass;
use Kirby\Form\Form;
use Kirby\Form\Fields as KirbyFields;

/**
 * Kirby Google Translate Package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://github.com/owebstudio/kirby-google-translate
 * @copyright oweb studio
 * @license   https://github.com/owebstudio/.github/blob/main/LICENSE.md
 */
class Translate
{
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function run(): array
    {
        return static::values($this->form()->fields(), $this->request, false);
    }

    /**
     * Returns the form of the page in the source language
     */
    public function form(): Form
    {
        return Form::for(
            model: $this->request->page(),
            language: $this->request->sourceLanguage()
        );
    }

    /**
     * Checks if the value of the field can be translated
     */
    public static function accepts(Field|FieldClass $field): bool
    {
        // do not add fields without a value (info, headline …),
        // disabled or non-translatable fields
        return
            $field->hasValue() === true &&
            (method_exists($field, 'isDisabled') === false || $field->isDisabled() === false) &&
            $field->translate() !== false;
    }

    public static function values(KirbyFields $fields, Request $request, bool $nested = true): array
    {
        $data = [];

        /** @var \Kirby\Form\Field $field */
        foreach ($fields as $field) {
            $name = $field->name();
            $type = $field->type();

            if (static::accepts($field) === false) {
                continue;
            }

            // only translate the selected fields and don't overwrite translated content
            // unless enabled (nested fields of structures and blocks are checked with their parent field)
            if (
                $nested === false &&
                (
                    $request->selected($name) === false ||
                    ($request->overwrite() === false && $request->translated($name) === true)
                )
            ) {
                continue;
            }

            // translate only supported field types
            if ($fieldType = Fields::get($type, $field, $request)) {
                $data[$name] = $fieldType->value();
            } else {
                $data[$name] = $field->toFormValue();
            }
        }

        return $data;
    }
}
