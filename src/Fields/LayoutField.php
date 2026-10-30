<?php

namespace Oweb\GoogleTranslate\Fields;

use Kirby\Cms\App as Kirby;
use Oweb\GoogleTranslate\Translate;

/**
 * Blocks field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class LayoutField extends Field
{
    public function value(): string|array
    {
        $field = $this->field();
        $value = $field->value();

        $coreBlockTypes = ['heading', 'list', 'markdown', 'quote', 'text'];
        $allowedBlockTypes = Kirby::instance()->option('owebstudio.google-translate.blockTypes', []);
        $blockTypes = array_merge($coreBlockTypes, $allowedBlockTypes);

        foreach ($value as $lIndex => $layout) {
            foreach ($layout['columns'] ?? [] as $cIndex => $column) {
                foreach ($column['blocks'] ?? [] as $bIndex => $block) {
                    $type = $block['type'];

                    // only allowed block types will translated
                    if (in_array($type, $blockTypes) === true) {
                        $blockFields = $field->fields($block['type']);
                        $fields = $this->fill($field->form($blockFields, $block['content']), $block['content'])->fields();

                        $content = Translate::values($fields, $this->request());
                        $value[$lIndex]['columns'][$cIndex]['blocks'][$bIndex]['content'] = $content;
                    }
                }
            }
        }

        return $this->save($value);
    }

    public function save($value)
    {
        return $value;
    }
}
