<?php

namespace Oweb\GoogleTranslate\Fields;

use Oweb\GoogleTranslate\Translate;

/**
 * Object field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
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
