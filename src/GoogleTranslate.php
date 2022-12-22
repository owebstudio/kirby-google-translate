<?php

namespace Oweb\GoogleTranslate;

use Exception;
use InvalidArgumentException;
use Kirby\Cms\App as Kirby;
use Google\Cloud\Translate\V2\TranslateClient;

/**
 * Kirby Google Translate Package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://owebstudio.com
 * @copyright oweb studio
 * @license   MIT
 */
class GoogleTranslate
{
    protected static ?TranslateClient $client = null;

    protected static function client(): TranslateClient
    {
        if (static::$client === null) {
            // check translate api key
            $apiKey = Kirby::instance()->option('owebstudio.google-translate.apiKey');

            if (empty($apiKey) === true) {
                throw new InvalidArgumentException('Google Translation API KEY not defined yet!');
            }

            static::$client = new TranslateClient([
                'key' => $apiKey
            ]);
        }

        return static::$client;
    }

    public static function translate(string $string, string $language, string $format = 'text'): string
    {
        $results = static::translateBatch([$string], $language, $format);
        return current($results);
    }

    public static function translateBatch(array $strings, string $language, string $format = 'text'): array
    {
        try {
            $results = static::client()
                ->translateBatch(
                    array_values($strings),
                    [
                        'format' => $format,
                        'target' => $language
                    ]
                );
        } catch (Exception $e) {
            $errorMessage = 'Google Translation failed!';

            if ($error = json_decode($e->getMessage(), true)) {
                $errorMessage = $error['error']['message'] ?? $errorMessage;
            }

            throw new Exception($errorMessage);
        }

        if (empty($results) === true) {
            throw new InvalidArgumentException('Google Translation failed!');
        }

        if (empty($results['error']) === false) {
            throw new Exception($results['error']['message'] ?? 'Google Translation failed!');
        }

        return array_map(function ($result) {
            $text = $result['text'];
            $text = html_entity_decode($text, ENT_QUOTES);
            $text = iconv(mb_detect_encoding($text, mb_detect_order(), true), 'UTF-8', $text);

            return $text;
        }, $results);

    }
}
