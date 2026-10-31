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
class BlocksField extends Field
{
    public function value(): string|array
    {
        $value = $this->field()->value();

        $coreBlockTypes = ['heading', 'list', 'markdown', 'quote', 'text'];
        $allowedBlockTypes = Kirby::instance()->option('owebstudio.google-translate.blockTypes', []);
        $blockTypes = array_merge($coreBlockTypes, $allowedBlockTypes);

        foreach ($value as &$block) {
            $type = $block['type'];

            // only allowed block types will translated
            if (in_array($type, $blockTypes) === true) {
                $blockFields = $this->field()->fields($block['type']);
                $fields = $this->fill($this->field()->form($blockFields), $block['content'])->fields();

                $block['content'] = Translate::values($fields, $this->request());
            }
        }

        return $this->save($value);
    }

    public function save($value)
    {
        return $value;
    }
}
