<?php

namespace Oweb\GoogleTranslate;

use Closure;
use Kirby\Cms\App as Kirby;
use Kirby\Data\Json;
use Kirby\Exception\Exception;
use Kirby\Exception\LogicException;
use Kirby\Http\Remote;
use Throwable;

/**
 * Kirby Google Translate Package
 *
 * @package   Oweb
 * @author    oweb studio <support@owebstudio.com>
 * @link      https://github.com/owebstudio/kirby-google-translate
 * @copyright oweb studio
 * @license   https://github.com/owebstudio/.github/blob/main/LICENSE.md
 */
class GoogleTranslate
{
    protected static string $endpoint = 'https://translation.googleapis.com/language/translate/v2';

    /**
     * Limits of a single request to the Google API
     * (128 texts and a request size of 204,800 bytes)
     */
    protected static int $maxStrings = 128;
    protected static int $maxBytes = 100000;

    /**
     * Texts that are requested while collecting,
     * by language and format
     */
    protected static array|null $queue = null;

    /**
     * Translated texts by language, format and original text
     */
    protected static array $translations = [];

    /**
     * Runs the callback without calling the API and collects all requested
     * texts, translates them in as few requests as possible and keeps the
     * results for the next calls
     */
    public static function prefetch(Closure $callback): void
    {
        static::$queue = [];

        try {
            $callback();
            $queue = static::$queue;
        } finally {
            static::$queue = null;
        }

        foreach ($queue as $language => $formats) {
            foreach ($formats as $format => $strings) {
                static::translateBatch(array_keys($strings), $language, $format);
            }
        }
    }

    public static function translate(string $string, string $language, string $format = 'text'): string
    {
        $results = static::translateBatch([$string], $language, $format);
        return current($results);
    }

    public static function translateBatch(array $strings, string $language, string $format = 'text'): array
    {
        $results = [];
        $missing = [];

        foreach ($strings as $key => $string) {
            $string = (string)$string;

            if (trim($string) === '') {
                // nothing to translate
                $results[$key] = $string;
            } elseif (isset(static::$translations[$language][$format][$string]) === true) {
                $results[$key] = static::$translations[$language][$format][$string];
            } elseif (static::$queue !== null) {
                // collecting: return the text as it is for now
                static::$queue[$language][$format][$string] = true;
                $results[$key] = $string;
            } else {
                $missing[$string] = true;
            }
        }

        foreach (static::chunks(array_keys($missing)) as $chunk) {
            foreach (static::request($chunk, $language, $format) as $index => $translation) {
                static::$translations[$language][$format][$chunk[$index]] = $translation;
            }
        }

        // keep the keys and order of the given texts
        $translated = [];

        foreach ($strings as $key => $string) {
            $translated[$key] = $results[$key] ?? static::$translations[$language][$format][(string)$string];
        }

        return $translated;
    }

    /**
     * Splits the texts into chunks that fit into a single request
     */
    protected static function chunks(array $strings): array
    {
        $chunks = [];
        $chunk = [];
        $bytes = 0;

        foreach ($strings as $string) {
            $size = strlen($string);

            if (
                $chunk !== [] &&
                (count($chunk) >= static::$maxStrings || $bytes + $size > static::$maxBytes)
            ) {
                $chunks[] = $chunk;
                $chunk = [];
                $bytes = 0;
            }

            $chunk[] = $string;
            $bytes += $size;
        }

        if ($chunk !== []) {
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    /**
     * Sends the texts to the Google API and
     * returns the translations in the same order
     */
    protected static function request(array $strings, string $language, string $format): array
    {
        // check translate api key
        $apiKey = Kirby::instance()->option('owebstudio.google-translate.apiKey');

        if (empty($apiKey) === true) {
            throw new LogicException(
                key: 'owebstudio.google-translate.apiKey',
                fallback: 'The Google Translate API key is not set'
            );
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
        } catch (Throwable) {
            throw new Exception(
                key: 'owebstudio.google-translate.failed',
                fallback: 'The translation with Google failed'
            );
        }

        $results = $response->json() ?? [];

        if ($response->code() !== 200) {
            throw new Exception(
                message: $results['error']['message'] ?? null,
                key: 'owebstudio.google-translate.failed',
                fallback: 'The translation with Google failed'
            );
        }

        $results = $results['data']['translations'] ?? [];

        if (count($results) !== count($strings)) {
            throw new Exception(
                key: 'owebstudio.google-translate.failed',
                fallback: 'The translation with Google failed'
            );
        }

        return array_map(function ($result) {
            $text = $result['translatedText'];
            $text = html_entity_decode($text, ENT_QUOTES);
            $text = iconv(mb_detect_encoding($text, mb_detect_order(), true), 'UTF-8', $text);

            return $text;
        }, array_values($results));
    }
}
