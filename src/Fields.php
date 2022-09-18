<?php

namespace Oweb\GoogleTranslate;

use Kirby\Cms\App as Kirby;
use Kirby\Form\Field;
use Kirby\Form\FieldClass;
use Oweb\GoogleTranslate\Fields\Field as TranslateField;
use Oweb\GoogleTranslate\Fields\BlocksField;
use Oweb\GoogleTranslate\Fields\ListField;
use Oweb\GoogleTranslate\Fields\StructureField;
use Oweb\GoogleTranslate\Fields\TextField;
use Oweb\GoogleTranslate\Fields\TextareaField;
use Oweb\GoogleTranslate\Fields\WriterField;

/**
 * Kirby Google Translate Package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class Fields
{
    protected static ?array $types = null;

    public static function types(): ?array
    {
        if (static::$types !== null) {
            return static::$types;
        }

        /*
        $coreFieldTypes = [
            'layout' => true,
        ];
        */

        $coreFieldTypes = [
            'blocks' => BlocksField::class,
            'list' => ListField::class,
            'structure' => StructureField::class,
            'text' => TextField::class,
            'textarea' => TextareaField::class,
            'writer' => WriterField::class,
        ];

        $allowedFieldTypes = Kirby::instance()->option('owebstudio.google-translate.fieldTypes', []);

        static::$types = array_replace($coreFieldTypes, $allowedFieldTypes);

        return static::$types;
    }

    public static function has(string $type): bool
    {
        return array_key_exists($type, static::types());
    }

    public static function get(string $type, Field|FieldClass $field, Request $request): ?TranslateField
    {
        if ($fieldClass = static::types()[$type] ?? null) {
            return new $fieldClass($field, $request);
        }

        return null;
    }
}
