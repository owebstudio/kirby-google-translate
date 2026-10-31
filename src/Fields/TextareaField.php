<?php

namespace Oweb\GoogleTranslate\Fields;

use Oweb\GoogleTranslate\GoogleTranslate;

/**
 * Textarea field
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://github.com/owebstudio/kirby-google-translate
 * @copyright oweb studio
 * @license   https://github.com/owebstudio/.github/blob/main/LICENSE.md
 */
class TextareaField extends Field
{
    public function value(): string|array
    {
        $value = $this->field()->value();

        $value = $this->kirbytagsBefore($value);
        $result = $this->translate($value);
        $value = $this->kirbytagsAfter($result);

        return $this->save($value);
    }

    public function save($value)
    {
        return $value;
    }

    /**
     * Encloses kirbytags in "`" before sending them to Google Translate.
     * This is sample `(link: https://google.com)` for website)
     */
    private function kirbytagsBefore(string $value): string
    {
        return preg_replace($this->regex(), '`$1`', $value);
    }

    /**
     * Kirbytags enclosed in "`" after translate are reverted.
     */
    private function kirbytagsAfter(string $value): string
    {
        $value = str_replace('`(', '(', $value);
        $value = str_replace(')`', ')', $value);

        return $value;
    }

    private function regex()
    {
        return '!
            (?=[^\]])               # positive lookahead that matches a group after the main expression without including ] in the result
            (?=\([a-z0-9_-]+:)      # positive lookahead that requires starts with ( and lowercase ASCII letters, digits, underscores or hyphens followed with : immediately to the right of the current location
            (\(                     # capturing group 1
                (?:[^()]+|(?1))*+   # repetitions of any chars other than ( and ) or the whole group 1 pattern (recursed)
            \))                     # end of capturing group 1
        !isx';
    }
}
