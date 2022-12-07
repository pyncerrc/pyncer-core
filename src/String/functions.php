<?php
namespace Pyncer\String;

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
 * @param string $s A string to check against
 * @param string $with A value the string must end with
 */
function ensure_ends_with(string $s, string $with): string
{
    $s = strval($s);
    $with = strval($with);

    $len = strlen($with);
    $pos = strlen($s) - $len;
    if (substr($s, $pos, $len) != $with) {
        return $s . $with;
    } else {
        return $s;
    }
}

/**
 * Returns the string prepended with another if it doesn't
 * already start with it
 *
 * @param string $s A string to check against
 * @param string $with A value the string must start with
 */
function ensure_starts_with(string $s, string $with): string
{
    $s = strval($s);
    $with = strval($with);

    $len = strlen($with);
    if (substr($s, 0, $len) != $with) {
        return $with . $s;
    } else {
        return $s;
    }
}

function pos_array(string $haystack, array $needles, ?int $offset = null): ?array
{
    if ($offset === null) {
        $offset = 0;
    }

    $index = null;

    foreach ($needles as $n) {
        $pos = strpos($haystack, $n, $offset);

        if ($pos === false) {
            continue;
        }

        if ($index === null || $pos < $index[1]) {
            $index = [$n, $pos];
        } elseif ($pos == $index[1] && strlen($n) > strlen($index[1])) { // Greedy needle
            $index[0] = $n;
        }
    }

    return $index;
}

function rpos_array(string $haystack, array $needles, ?int $offset = null): ?array
{
    if ($offset === null) {
        $offset = 0;
    }

    $index = null;

    foreach ($needles as $n) {
        $pos = strrpos($haystack, $n, $offset);

        if ($pos === false) {
            continue;
        }

        if (!$index || $pos > $index[1]) {
            $index = [$n, $pos];
        } elseif ($pos == $index[1] && strlen($n) > strlen($index[1])) { // Greedy needle
            $index[0] = $n;
        }
    }

    return $index;
}

function rpos_index(string $haystack, string $needle, ?int $index = null): int
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

function line_count(string $s): int
{
    // Normalize newlines
    $s = str_replace("\r\n", "\n", $s);
    $s = str_replace("\r", "\n", $s);

    $s = explode("\n", $s);

    return count($s);
}

function remove_characters(string $s, string $pattern): string
{
    $new_str = '';

    $s = strval($s);
    $len = strlen($s);
    for ($i = 0; $i < $len; ++$i) {
        if (!preg_match($pattern, $s[$i])) {
            $new_str .= $s[$i];
        }
    }

    return $new_str;
}

function retain_characters(string $s, string $pattern): string
{
    $new_str = '';

    $s = strval($s);
    $len = strlen($s);
    for ($i = 0; $i < $len; ++$i) {
        if (preg_match($pattern, $s[$i])) {
            $new_str .= $s[$i];
        }
    }

    return $new_str;
}

function char_array(string $s): array
{
    $chars = [];

    $len = len($s);
    for ($i = 0; $i < $len; ++$i) {
        $chars[] = sub($s, $i, 1);
    }

    return $chars;
}

/**
 * Returns the specifed string with the specifed start and end string removed
 *
 * @param string $s A string to remove surounding strings from
 * @param string $start A value the string must start with to remove
 * @param mixed $end A value the string must ends with to remove
 */
function trim_string(string $s, string $start, ?string $end = null, bool $once = false): string
{
    if ($end === null) {
        $end = $start;
    }

    $s = ltrim_string($s, $start, $once);
    $s = rtrim_string($s, $end, $once);

    return $s;
}

function ltrim_string(string $s, string $remove, bool $once = false): string
{

    $s = strval($s);
    $remove = strval($remove);

    if ($s === $remove) {
        return '';
    }

    $len = strlen($remove);

    while (true) {
        if (substr($s, 0, $len) === $remove) {
            $s = substr($s, $len);
            if (!$once) {
                continue;
            }
        }

        break;
    }

    return $s;
}

function rtrim_string(string $s, string $remove, bool $once = false): string
{
    $s = strval($s);
    $remove = strval($remove);

    if ($s === $remove) {
        return '';
    }

    $len = -strlen($remove);

    while (true) {
        if (substr($s, $len) === $remove) {
            $s = substr($s, 0, $len);
            if (!$once) {
                continue;
            }
        }

        break;
    }

    return $s;
}

function trim_all(string $s, array $start, ?array $end = null): string
{
    if ($end === null) {
        $end = $start;
    }

    $s = ltrim_all($s, $start);
    $s = rtrim_all($s, $end);

    return $s;
}

/**
 * @param string $s A string to check against
 * @param array $with An array of values the string must end with
 */
function rtrim_all(string $s, array $with): string
{
    // TODO: Add sort function so longest match gets removed
    // TODO: Encase in while so all instances are taken care of...
    foreach ($with as $value) {
        if ($s === strval($value)) {
            return '';
        }

        $pos = strlen($s) - strlen($value);
        if ($pos > 0 && substr($s, $pos) == $value) {
            return substr($s, 0, $pos);
        }
    }

    return $s;
}

/**
 * @param string $s A string to check against
 * @param array $with An array of values the string must start with
 */
function ltrim_all(string $s, array $with): string
{
    // TODO: Add sort function so longest match gets removed
    // TODO: Encase in while so all instances are taken care of...
    foreach ($with as $value) {
        if ($s === strval($value)) {
            return '';
        }

        $len = strlen($value);
        if (substr($s, 0, $len) == $value) {
            return substr($s, $len);
        }
    }

    return $s;
}

function ucfirst(string $s): string
{
    switch (len($s) ) {
        case 0:
            return '';
        case 1:
            return to_upper($s);
        default:
            preg_match('/^(.{1})(.*)$/us', $s, $matches);
            return to_upper($matches[1]) . $matches[2];
    }
}
function ucwords(string $s): string
{
    // Note: [\x0c\x09\x0b\x0a\x0d\x20] matches:
    // form feeds, horizontal tabs, vertical tabs, linefeeds and carriage returns
    // This corresponds to the definition of a "word" defined at http://www.php.net/ucwords
    $pattern = '/(^|([\x0c\x09\x0b\x0a\x0d\x20]+))([^\x0c\x09\x0b\x0a\x0d\x20]{1})[^\x0c\x09\x0b\x0a\x0d\x20]*/u';
    return preg_replace_callback(
        $pattern,
        function ($matches)
        {
            $leadingws = $matches[2];
            $ucfirst = to_upper($matches[3]);
            $ucword = sub_replace(ltrim($matches[0]), $ucfirst, 0, 1);
            return $leadingws . $ucword;
        },
        $s
    );
}
function sub_replace(string $s, string $replace, int $offset, ?int $length = null): string
{
    preg_match_all('/./us', $s, $ar);
    preg_match_all('/./us', $replace, $rar);

    if ($length === null) {
        $length = len($s);
    }

    array_splice($ar[0], $offset, $length, $rar[0]);

    return join('', $ar[0]);
}
function convert_kana(string $s, string $mode = 'KV'): string
{
    return mb_convert_kana($s, $mode, PYNCER_ENCODING);
}
function split(string $s, int $length = 1): array
{
    return mb_str_split($s, $option, PYNCER_ENCODING);
}
function ipos(string $haystack, string $needle, ?int $offset = null): int|false
{
    return mb_stripos($haystack, $needle, $offset, PYNCER_ENCODING);
}
function istr(string $haystack, string $needle, bool $beforeNeedle = false): string|false
{
    return mb_stristr($haystack, $needle, $beforeNeedle, PYNCER_ENCODING);
}
function len(string $s): int
{
    return mb_strlen($s, PYNCER_ENCODING);
}
function pos(string $haystack, string $needle, int $offset = 0): int|false
{
    return mb_strpos($haystack, $needle, $offset, PYNCER_ENCODING);
}
function rchr(string $haystack, string $needle, bool $beforeNeedle = null): string|false
{
    return mb_strrchr($haystack, $needle, $beforeNeedle, PYNCER_ENCODING);
}
function richr(string $haystack, string $needle, bool $beforeNeedle = null): string|false
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
function to_lower(string $s): string
{
    return mb_strtolower($s, PYNCER_ENCODING);
}
function to_upper(string $s): string
{
    return mb_strtoupper($s, PYNCER_ENCODING);
}
function sub_count(string $haystack, string $needle): int
{
    return mb_substr_count($haystack, $needle, PYNCER_ENCODING);
}
function sub(string $s, int $start, ?int $length = null): string
{
    return mb_substr($s, $start, $length, PYNCER_ENCODING);
}
