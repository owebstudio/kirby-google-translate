<?php

namespace Oweb\GoogleTranslate\Fields;

use Oweb\GoogleTranslate\GoogleTranslate;

/**
 * Textarea field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class WriterField extends Field
{
    public string $format = 'html';

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
