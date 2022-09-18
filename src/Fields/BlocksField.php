<?php

namespace Oweb\GoogleTranslate\Fields;

use Oweb\GoogleTranslate\Translate;

/**
 * Structure field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class BlocksField extends Field
{
    public function value(): string|array
    {
        $data = [];

        foreach ($this->field()->value() as $block) {
            $blockFields = $this->field()->fields($block['type']);
            $fields = $this->field()->form($blockFields, $block['content'])->fields();

            $block['content'] = Translate::values($fields, $this->request());
            $data[] = $block;
        }

        return $this->save($data);
    }

    public function save($value)
    {
        return $this->field()->store($value);
    }
}
