<?php

namespace Oweb\GoogleTranslate;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Form\Form;
use Kirby\Form\Fields as KirbyFields;

/**
 * Kirby Google Translate Package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
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
        $form = Form::for(
            model: $this->request->page(),
            language: $this->request->sourceLanguage()
        );

        return static::values($form->fields(), $this->request, false);
    }

    public static function values(KirbyFields $fields, Request $request, bool $nested = true): array
    {
        $overwrite = Kirby::instance()->option('owebstudio.google-translate.overwrite', false);

        $data = [];

        /** @var \Kirby\Form\Field $field */
        foreach ($fields as $field) {
            $name = $field->name();
            $type = $field->type();

            // do not add fields without a value (info, headline …),
            // disabled or non-translatable fields
            if (
                $field->hasValue() === false ||
                (method_exists($field, 'isDisabled') === true && $field->isDisabled() === true) ||
                $field->translate() === false
            ) {
                continue;
            }

            // don't overwrite values if translated content exists and option disabled
            // (nested fields of structures and blocks are checked with their parent field)
            if (
                $nested === false &&
                $overwrite === false &&
                $request->translated($name) === true
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
