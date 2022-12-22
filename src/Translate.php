<?php

namespace Oweb\GoogleTranslate;

use Kirby\Cms\App as Kirby;
use Kirby\Cms\Page;
use Kirby\Form\Form;
use Kirby\Form\Fields as KirbyFields;
use Google\Cloud\Translate\V2\Translaterequest;

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
        $form = Form::for($this->request->page(), [
            'language' => $this->request->sourceLanguage()
        ]);

        return static::values($form->fields(), $this->request);
    }

    public static function values(KirbyFields $fields, Request $request): array
    {
        $page = $request->page();
        $overwrite = Kirby::instance()->option('owebstudio.google-translate.overwrite', false);

        $data = [];

        /** @var \Kirby\Form\Field $field */
        foreach ($fields as $field) {
            $name = $field->name();
            $type = $field->type();

            // do not add disabled or non-translatable fields
            if ($field->disabled() === true || $field->translate() === false) {
                continue;
            }

            // don't overwrite values if translated content exists and option disabled
            if (
                $overwrite === false &&
                $page->translation($request->targetLanguage())->exists() === true &&
                empty($page->content($request->targetLanguage())?->get($name)?->value()) === false
            ) {
                continue;
            }

            // translate only supported field types
            if ($fieldType = Fields::get($type, $field, $request)) {
                $data[$name] = $fieldType->value();
            } else {
                $data[$name] = $field->content() ?? $field->value();
            }
        }

        return $data;
    }
}
