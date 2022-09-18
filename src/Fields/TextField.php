<?php

namespace Oweb\GoogleTranslate\Fields;

use Oweb\GoogleTranslate\GoogleTranslate;

/**
 * Text field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class TextField extends Field
{
    public function value(): string|array
    {
        $value = $this->field()->value();
        $result = $this->translate($value);

        return $this->save($result);
    }

    public function save($value)
    {
        return $value;
    }
}
