<?php

namespace Oweb\GoogleTranslate\Fields;

use Oweb\GoogleTranslate\Translate;

/**
 * Object field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://github.com/owebstudio/kirby-google-translate
 * @copyright oweb studio
 * @license   https://github.com/owebstudio/.github/blob/main/LICENSE.md
 */
class ObjectField extends Field
{
    public function value(): string|array
    {
        $value = $this->field()->value();

        // an empty object stays empty
        if (empty($value) === true) {
            return $this->save($value);
        }

        $fields = $this->fill($this->field()->form(), $value)->fields();

        return $this->save(Translate::values($fields, $this->request()));
    }

    public function save($value)
    {
        return $value;
    }
}
