<?php
namespace Pyncer\String;

use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\UnexpectedValueException;

use function array_splice;
use function count;
use function explode;
use function join;
use function ltrim;
use function mb_convert_kana;
use function mb_str_split;
use function mb_stripos;
use function mb_stristr;
use function mb_strlen;
use function mb_strpos;
use function mb_strrchr;
use function mb_strrichr;
use function mb_strripos;
use function mb_strrpos;
use function mb_strstr;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function mb_substr_count;
use function min;
use function preg_match;
use function preg_match_all;
use function preg_replace_callback;
use function str_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strval;
use function substr;

use const Pyncer\ENCODING as PYNCER_ENCODING;

function nullify(?string $value, mixed $default = null): mixed
{
    if ($value === '' || $value === null) {
        return $default;
    }

    return $value;
}

/**
 * Returns the string appended with another if it doesn't
 * already end with it
 *
 * @param string $string A string to check against
 * @param string $with A value the string must end with
 */
function ensure_ends_with(string $string, string $with): string
{
    $len = strlen($with);
    $pos = strlen($string) - $len;
    if (substr($string, $pos, $len) !== $with) {
        return $string . $with;
    } else {
        return $string;
    }
}

/**
 * Returns the string prepended with another if it doesn't
 * already start with it
 *
 * @param string $string A string to check against
 * @param string $with A value the string must start with
 */
function ensure_starts_with(string $string, string $with): string
{
    $len = strlen($with);
    if (substr($string, 0, $len) !== $with) {
        return $with . $string;
    } else {
        return $string;
    }
}

/**
 * @param string $haystack The string to search in.
 * @param array<string> $needles An array of strings to search for.
 * @return bool True if any of the needles were found, otherwise false.
 */
function contains_array(string $haystack, array $needles): bool
{
    foreach ($needles as $needle) {
        if (str_contains($haystack, $needle)) {
            return true;
        }
    }

    return false;
}

/**
 * @param string $haystack The string to search in.
 * @param array<string> $needles An array of strings to search for.
 * @param int $offset
 * @return array{'needle': string, 'index': int} An array containing the
 *      needle and index position if a needle is found, otherwise false.
 */
function pos_array(string $haystack, array $needles, int $offset = 0): array|false
{
    $index = false;

    foreach ($needles as $needle) {
        $pos = pos($haystack, $needle, $offset);

        if ($pos === false) {
            continue;
        }

        if ($index === false || $pos < $index['index']) {
            $index = [
                'needle' => $needle,
                'index' => $pos,
            ];
        } elseif ($pos == $index['index'] &&
            len($needle) > len($index['needle'])
        ) {
            // Greedy needle
            $index['needle'] = $needle;
        }
    }

    return $index;
}

/**
 * @param string $haystack The string to search in.
 * @param array<string> $needles An array of strings to search for.
 * @param int $offset
 * @return array{'needle': string, 'index': int} An array containing the
 *      needle and index position if a needle is found, otherwise false.
 */
function rpos_array(string $haystack, array $needles, int $offset = 0): array|false
{
    $index = false;

    foreach ($needles as $needle) {
        $pos = rpos($haystack, $needle, $offset);

        if ($pos === false) {
            continue;
        }

        if ($index === false || $pos > $index['index']) {
            $index = [
                'needle' => $needle,
                'index' => $pos,
            ];
        } elseif ($pos == $index['index'] &&
            len($needle) > len($index['needle'])
        ) {
            // Greedy needle
            $index['needle'] = $needle;
        }
    }

    return $index;
}

function rpos_index(string $haystack, string $needle, ?int $index = null): int|false
{
    if ($index === null) {
        $index = len($haystack);
    } else {
        if ($index < 0) {
            throw new UnexpectedValueException('Index must be greater than zero.');
        }

        $index = min(len($haystack), $index);
    }

    return rpos($haystack, $needle, -(len($haystack) - $index));
}

function replace_repeating(string $search, string $replace, string $subject): string
{
    $len = len($subject);
    $len_search = len($search);

    $new_str = '';
    $in_repeat = false;

    for ($i = 0; $i < $len; ++$i) {
        if (sub($subject, $i, $len_search) == $search) {
            if (!$in_repeat) {
                $new_str .= $replace;
                $in_repeat = true;
            }

            $i += ($len_search - 1);
        } else {
            $in_repeat = false;
            $new_str .= sub($subject, $i, 1);
        }
    }

    return $new_str;
}

function line_count(string $string): int
{
    // Normalize newlines
    $string = str_replace("\r\n", "\n", $string);
    $string = str_replace("\r", "\n", $string);

    $string = explode("\n", $string);

    return count($string);
}

function remove_characters(string $string, string $pattern): string
{
    $new_str = '';

    $len = strlen($string);
    for ($i = 0; $i < $len; ++$i) {
        if (!preg_match($pattern, $string[$i])) {
            $new_str .= $string[$i];
        }
    }

    return $new_str;
}

function retain_characters(string $string, string $pattern): string
{
    $new_str = '';

    $len = strlen($string);
    for ($i = 0; $i < $len; ++$i) {
        if (preg_match($pattern, $string[$i])) {
            $new_str .= $string[$i];
        }
    }

    return $new_str;
}

/**
 * Returns a string with the specifed start and end strings trimmed from the
 * beginning and end.
 *
 * @param string $string A string to remove surounding strings from
 * @param string $start A value the string must start with to remove
 * @param null|string $end A value the string must ends with to remove
 */
function trim_string(
    string $string,
    string $start,
    ?string $end = null,
    bool $once = false
): string
{
    if ($end === null) {
        $end = $start;
    }

    $string = ltrim_string($string, $start, $once);
    $string = rtrim_string($string, $end, $once);

    return $string;
}

function ltrim_string(
    string $string,
    string $remove,
    bool $once = false
): string
{
    if ($string === $remove) {
        return '';
    }

    $len = strlen($remove);

    while (true) {
        if (substr($string, 0, $len) === $remove) {
            $string = substr($string, $len);
            if (!$once) {
                continue;
            }
        }

        break;
    }

    return $string;
}

function rtrim_string(
    string $string,
    string $remove,
    bool $once = false
): string
{
    if ($string === $remove) {
        return '';
    }

    $len = -strlen($remove);

    while (true) {
        if (substr($string, $len) === $remove) {
            $string = substr($string, 0, $len);
            if (!$once) {
                continue;
            }
        }

        break;
    }

    return $string;
}

/**
 * Trims an array of values from the beginning and end of the a string.
 *
 * If the third parameter is not specified, the the sencond parameter will be
 * used to trim the end of the string.
 *
 * @param string $string A String to trim.
 * @param array<string> $start An array of values to left trim with.
 * @param null|array<string> $end An array of values to right trim with.
 * @return string The trimmed string.
 */
function trim_all(string $string, array $start, ?array $end = null): string
{
    $end ??= $start;

    $string = ltrim_all($string, $start);
    $string = rtrim_all($string, $end);

    return $string;
}

/**
 * Trims an array of values from the end of the a string.
 *
 * @param string $string A string to right trim.
 * @param array<string> $with An array of values to right trim.
 * @return string The trimmed string.
 */
function rtrim_all(string $string, array $with): string
{
    // TODO: Add sort function so longest match gets removed
    // TODO: Encase in while so all instances are taken care of...
    foreach ($with as $value) {
        if ($string === strval($value)) {
            return '';
        }

        $pos = strlen($string) - strlen($value);
        if ($pos > 0 && substr($string, $pos) == $value) {
            return substr($string, 0, $pos);
        }
    }

    return $string;
}

/**
 * Trims an array of values from the beginning of the a string.
 *
 * @param string $string A string to left trim.
 * @param array<string> $with An array of values to left trim.
 * @return string The trimmed string.
 */
function ltrim_all(string $string, array $with): string
{
    // TODO: Add sort function so longest match gets removed
    // TODO: Encase in while so all instances are taken care of...
    foreach ($with as $value) {
        if ($string === strval($value)) {
            return '';
        }

        $len = strlen($value);
        if (substr($string, 0, $len) == $value) {
            return substr($string, $len);
        }
    }

    return $string;
}

function ucfirst(string $string): string
{
    switch (len($string) ) {
        case 0:
            return '';
        case 1:
            return to_upper($string);
        default:
            preg_match('/^(.{1})(.*)$/us', $string, $matches);
            return to_upper($matches[1]) . $matches[2];
    }
}

function ucwords(string $string): string
{
    // Note: [\x0c\x09\x0b\x0a\x0d\x20] matches:
    // form feeds, horizontal tabs, vertical tabs, linefeeds and carriage returns
    // This corresponds to the definition of a "word" defined at http://www.php.net/ucwords
    $pattern = '/(^|([\x0c\x09\x0b\x0a\x0d\x20]+))([^\x0c\x09\x0b\x0a\x0d\x20]{1})[^\x0c\x09\x0b\x0a\x0d\x20]*/u';
    $result = preg_replace_callback(
        $pattern,
        function ($matches)
        {
            $leadingws = $matches[2];
            $ucfirst = to_upper($matches[3]);
            $ucword = sub_replace(ltrim($matches[0]), $ucfirst, 0, 1);
            return $leadingws . $ucword;
        },
        $string
    );

    return strval($result);
}

function sub_replace(string $string, string $replace, int $offset, ?int $length = null): string
{
    preg_match_all('/./us', $string, $ar);
    preg_match_all('/./us', $replace, $rar);

    if ($length === null) {
        $length = len($string);
    }

    array_splice($ar[0], $offset, $length, $rar[0]);

    return join('', $ar[0]);
}

function convert_kana(string $string, string $mode = 'KV'): string
{
    return mb_convert_kana($string, $mode, PYNCER_ENCODING);
}

/**
 * Splits a string into an array of its characters.
 *
 * @param string $string The string to split.
 * @param int $length The number of characters in each array entry.
 * @return array<string> The split string.
 */
function split(string $string, int $length = 1): array
{
    if ($length < 1) {
        throw new InvalidArgumentException('Length must be greater than one.');
    }

    return mb_str_split($string, $length, PYNCER_ENCODING);
}

function ipos(string $haystack, string $needle, int $offset = 0): int|false
{
    return mb_stripos($haystack, $needle, $offset, PYNCER_ENCODING);
}

function istr(string $haystack, string $needle, bool $beforeNeedle = false): string|false
{
    return mb_stristr($haystack, $needle, $beforeNeedle, PYNCER_ENCODING);
}

function len(string $string): int
{
    return mb_strlen($string, PYNCER_ENCODING);
}

function pos(string $haystack, string $needle, int $offset = 0): int|false
{
    return mb_strpos($haystack, $needle, $offset, PYNCER_ENCODING);
}

function rchr(string $haystack, string $needle, bool $beforeNeedle = false): string|false
{
    return mb_strrchr($haystack, $needle, $beforeNeedle, PYNCER_ENCODING);
}

function richr(string $haystack, string $needle, bool $beforeNeedle = false): string|false
{
    return mb_strrichr($haystack, $needle, $beforeNeedle, PYNCER_ENCODING);
}

function ripos(string $haystack, string $needle, int $offset = 0): int|false
{
    return mb_strripos($haystack, $needle, $offset, PYNCER_ENCODING);
}

function rpos(string $haystack, string $needle, int $offset = 0): int|false
{
    return mb_strrpos($haystack, $needle, $offset, PYNCER_ENCODING);
}

function str(string $haystack, string $needle, bool $beforeNeedle = false): string|false
{
    return mb_strstr($haystack, $needle, $beforeNeedle, PYNCER_ENCODING);
}

function to_lower(string $string): string
{
    return mb_strtolower($string, PYNCER_ENCODING);
}

function to_upper(string $string): string
{
    return mb_strtoupper($string, PYNCER_ENCODING);
}

function sub_count(string $haystack, string $needle): int
{
    return mb_substr_count($haystack, $needle, PYNCER_ENCODING);
}

function sub(string $string, int $start, ?int $length = null): string
{
    return mb_substr($string, $start, $length, PYNCER_ENCODING);
}
