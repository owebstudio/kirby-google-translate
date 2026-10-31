<?php

namespace Oweb\GoogleTranslate\Fields;

use Oweb\GoogleTranslate\Translate;

/**
 * Structure field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://github.com/owebstudio/kirby-google-translate
 * @copyright oweb studio
 * @license   https://github.com/owebstudio/.github/blob/main/LICENSE.md
 */
class StructureField extends Field
{
    public function value(): string|array
    {
        $data = [];

        foreach ($this->field()->value() as $value) {
            $fields = $this->fill($this->field()->form(), $value)->fields();
            $data[] = Translate::values($fields, $this->request());
        }

        return $this->save($data);
    }

    public function save($value)
    {
        return $value;
    }
}
