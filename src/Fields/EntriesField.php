<?php

namespace Oweb\GoogleTranslate\Fields;

use Oweb\GoogleTranslate\Translate;

/**
 * Entries field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class EntriesField extends Field
{
    public function value(): string|array
    {
        $form = $this->field()->form();
        $data = [];

        // each entry is translated with the field type of the entries
        foreach ($this->field()->value() as $value) {
            $fields = $this->fill($form->reset(), [$value])->fields();
            $translated = Translate::values($fields, $this->request());

            $data[] = $translated === [] ? $value : current($translated);
        }

        return $this->save($data);
    }

    public function save($value)
    {
        return $value;
    }
}
