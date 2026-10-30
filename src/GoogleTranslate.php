<?php

namespace Oweb\GoogleTranslate;

use Exception;
use InvalidArgumentException;
use Kirby\Cms\App as Kirby;
use Kirby\Data\Json;
use Kirby\Http\Remote;

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
    protected static string $endpoint = 'https://translation.googleapis.com/language/translate/v2';

    public static function translate(string $string, string $language, string $format = 'text'): string
    {
        $results = static::translateBatch([$string], $language, $format);
        return current($results);
    }

    public static function translateBatch(array $strings, string $language, string $format = 'text'): array
    {
        // check translate api key
        $apiKey = Kirby::instance()->option('owebstudio.google-translate.apiKey');

        if (empty($apiKey) === true) {
            throw new InvalidArgumentException('Google Translation API KEY not defined yet!');
        }

        try {
            $response = Remote::request(static::$endpoint . '?key=' . rawurlencode($apiKey), [
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'data' => Json::encode([
                    'q' => array_values($strings),
                    'target' => $language,
                    'format' => $format
                ])
            ]);
        } catch (Exception) {
            throw new Exception('Google Translation failed!');
        }

        $results = $response->json() ?? [];

        if ($response->code() !== 200) {
            throw new Exception($results['error']['message'] ?? 'Google Translation failed!');
        }

        $results = $results['data']['translations'] ?? [];

        if (count($results) !== count($strings)) {
            throw new InvalidArgumentException('Google Translation failed!');
        }

        return array_map(function ($result) {
            $text = $result['translatedText'];
            $text = html_entity_decode($text, ENT_QUOTES);
            $text = iconv(mb_detect_encoding($text, mb_detect_order(), true), 'UTF-8', $text);

            return $text;
        }, $results);
    }
}
