<?php
namespace Pyncer\Array;

use function array_diff;
use function array_intersect;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_reverse;
use function array_search;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function explode as php_explode;
use function implode as php_implode;
use function in_array;
use function is_array;
use function is_int;
use function is_scalar;
use function iterator_to_array;
use function min;
use function Pyncer\String\len as pyncer_str_len;
use function Pyncer\String\sub as pyncer_str_sub;
use function rsort;
use function strlen;
use function substr;
use Traversable;

function nullify(?array $value, mixed $default = null): mixed
{
    if ($value === [] || $value === null) {
        return $default;
    }

    return $value;
}

/**
 * Ensures that the specified value is an array.
 *
 * @param mixed $value Value to ensure as an array
 * @param array $empty List of values that would represent an empty array
 */
function ensure_array(mixed $value, array $empty = []): array
{
    if (!is_array($value)) {
        if ($value instanceof Traversable) {
            return iterator_to_array($value, false);
        }

        if (in_array($value, $empty, true)) {
            return [];
        }

        return [$value];
    }

    return array_values($value);
}

/**
 * Ensures the specified array of keys exist in the specified array.
 *
 * @param mixed $array The array in which to ensure the keys exist in
 * @param array $keys An array of keys
 * @param mixed $default The default value of array keys that do not exist in the origional array
 */
function ensure_keys(array $array, array $keys, mixed $default = null): array
{
    foreach ($keys as $value) {
        if (!array_key_exists($value, $array)) {
            $array[$value] = $default;
        }
    }

    return $array;
}
/**
 * Sets any value in the specifed array that isnt set to the value in $values with the same key
 *
 * @param: mixed $array An array in which to ensure has certain values
 * @param: array $values Values to ensure exist in the array
 */
function ensure_values(array $array, array $values): array
{
    foreach ($values as $key => $value) {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $value;
        }
    }

    return $array;
}

/**
 * Sets any value in the specifed array that isnt set to the value in $values with the same key and
 * recursively going through each sub array values.
 *
 * @param: mixed $array An array in which to ensure has certain values
 * @param: array $values Values to ensure exist in the array
 */
function ensure_values_recursive(array $array, array $values): array
{
    foreach ($values as $key => $value) {
        if (is_array($value)) {
            $array[$key] = ensure_values_recursive($array[$key] ?? [], $value);
        } elseif (!array_key_exists($key, $array)) {
            $array[$key] = $value;
        }
    }

    return $array;
}

/**
 * Returns a new array of all values with a key in $keys, if the value is not set, the default value will be used if any.
 *
 * @param array $array Array to intersect
 * @param array $keys Keys to intersect with
 */
function intersect_keys(array $array, array $keys): array
{
    $newa = [];

    foreach ($keys as $key) {
        if (array_key_exists($key, $array)) {
            $newa[$key] = $array[$key];
        }
    }

    return $newa;
}

function difference_keys(array $array1, array $array2): array
{
    $keys = [];

    // This seems silly
    if (!array_is_list($array1)) {
        $array1 = array_keys($array1);
    }

    if (!array_is_list($array2)) {
        $array2 = array_keys($array2);
    }

    foreach ($array1 as $key) {
        $search = array_search($key, $array2);
        if ($search === false) {
            $keys[] = $key;
        } else {
            unset($array2[$search]);
        }
    }

    // Remaining keys not in array1
    foreach ($array2 as $key) {
        $keys[] = $key;
    }

    return $keys;
}

/**
 * Appends $array2 to the end of $array 1 as numeric keys
 *
 * @param mixed $array1 Initial array to merge
 * @param mixed $array2 Variable list of arrays to recursively merge
 */
function merge_safe(array ...$arrays): array
{
    $newa = [];

    foreach ($arrays as $array) {
        foreach ($array as $value) {
            $newa[] = $value;
        }
    }

    return $newa;
}
function merge_diff(array ...$arrays): array
{
    $intersected = array_intersect(...$arrays);

    $newa = [];

    foreach ($arrays as $array) {
         $newa = array_merge($newa, array_diff($array, $intersected));
    }

    return $newa;
}

function merge_unique(array ...$arrays): array
{
    return array_unique(array_merge(...$arrays));
}

function merge_recursive(iterable ...$arrays): array
{
    $newa = [];

    foreach ($arrays as $value) {
        foreach ($value as $key => $value2) {
            if (is_array($value2) && isset($newa[$key]) && is_array($newa[$key])) {
                $newa[$key] = merge_recursive($newa[$key], $value2);
            } else {
                $newa[$key] = $value2;
            }
        }
    }

    return $newa;
}

function merge_implode($glue, iterable ...$arrays): array
{
    $newa = [];

    foreach ($arrays as $array) {
        foreach ($array as $key => $value) {
            if (array_key_exists($key, $newa)) {
                $newa[$key] .= $glue . $value;
            } else {
                $newa[$key] = $value;
            }
        }
    }

    return $newa;
}

/**
 * Removes empty values from the specified array. Empty
 * Values consists of false, null and an empty string.
 *
 * @param array $array The array to remove empty values from
 */
function unset_empty(array $array): array
{
    $newa = [];

    $isList = array_is_list($array);

    foreach ($array as $key => $value) {
        if ($value === '' ||
            $value === false ||
            $value === 0 ||
            $value === 0.0 ||
            $value === [] ||
            $value === null
        ) {
            continue;
        }

        if ($isList) {
            $newa[] = $value;
        } else {
            $newa[$key] = $value;
        }
    }

    return $newa;
}
function unset_null(array $array): array
{
    $newa = [];

    foreach ($array as $key => $value) {
        if ($value !== null) {
            $newa[$key] = $value;
        }
    }

    return $newa;
}
/**
 * Unsets the specified keys from the specified array.
 *
 * @param array $array The array to unset values from
 * @param array $keys An array of keys to unset
 */
function unset_keys(array $array, array $keys): array
{
    foreach ($keys as $value) {
        unset($array[$value]);
    }

    return $array;
}
/**
* Removes the specified values from the specified array.
* Similar to array_diff, but does not reorder the array.
*
* @param mixed $array The array to remove values from
* @param mixed $values The values to remove
* @param mixed $reorder Set to true to reorder the array
* @return array
*/
function unset_values(array $array, array $values): array
{
    $newa = [];

    foreach ($array as $key => $value) {
        if (!in_array($value, $values, true)) {
            $newa[$key] = $value;
        }
    }

    return $newa;
}
function null_values(iterable $array) {
    $new = [];

    foreach ($array as $key => $value) {
        $new[$key] = null;
    }

    return $new;
}
function nullify_values(iterable $array, $default = null) {
    $new = [];

    foreach ($array as $key => $value) {
        $new[$key] = nullify($value, $default);
    }

    return $new;
}

function data_explode($delimiter, $s)
{
    $s = php_explode($delimiter, $s);
    $s = array_map('trim', $s);
    $s = unset_empty($s);
    return $s;
}
function data_implode($delimiter, iterable $array)
{
    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, false);
    }

    $array = array_map('trim', $array);
    $array = unset_empty($array);
    $array = php_implode($delimiter, $array);

    return $array;
}

/**
 * Counts the number of numeric keys in the array
 *
 * @param array $array The array to count
 */
function index_count(array $array): int
{
    $count = 0;

    $array = array_keys($array);
    foreach ($array as $key) {
        //if (preg_match('/^[0-9]+$/', $key)) {
        if (is_int($key)) {
            ++$count;
        }
    }

    return $count;
}
/**
 * Ensure the specified array has the specified number of values.
 *
 * @param mixed $array The array to ensure has the speicified number of values
 * @param int $count The number of values to ensure the array contains
 */
function ensure_index_count(array $array, $count, $default = null)
{
    $start = index_count($array);
    $len = $count - $start;

    if ($len <= 0) {
        return $array;
    }

    for ($i = 0; $i < $len; ++$i) {
        $array[] = $default;
    }

    return $array;
}
/**
 * Rebuild the array index so all numeric keys are in a squence
 * starting at 0 and non numeric keys are preserved.
 *
 * @param array $array The array to rebuild
 */
function reorder_index(array $array): array
{
    $newa = [];

    $index = -1;
    foreach ($array as $key => $value) {
        //if (preg_match('/^[0-9]+$/', $key)) {
        if (is_int($key)) {
            ++$index;
            $newa[$index] = $value;
        } else {
            $newa[$key] = $value;
        }
    }

    return $newa;
}
function indexed_values(array $array): array
{
    $newa = [];

    foreach ($array as $key => $value) {
        if (is_int($key)) {
            $newa[$key] = $value;
        }
    }

    return $newa;
}
function keyed_values(array $array): array
{
    $newa = [];

    foreach ($array as $key => $value) {
        if (!is_int($key)) {
            $newa[$key] = $value;
        }
    }

    return $newa;
}

function has_compositions(array $array): bool
{
    foreach ($array as $value) {
        if (!is_scalar($value) && $value !== null) {
            return true;
        }
    }

    return false;
}

function first_key(array $array): mixed
{
    if (!$array) {
        return null;
    }

    return array_keys($array)[0];
}
function first_value(array $array): mixed
{
    if (!$array) {
        return null;
    }

    return array_shift($array);
}
function last_key(array $array): mixed
{
    if (!$array) {
        return null;
    }

    $keys = array_keys($array);
    return $keys[count($keys) - 1];
}
function last_value(array $array): mixed
{
    if (!$array) {
        return null;
    }

    return array_pop($array);
}

/**
 * Returns a new array of all values of the existing array's sub array at the specified key
 *
 * @param array $array Array to get values from
 * @param string $valueKey Key to use as sub array value
 * @param null|string $indexKey Key to use as sub array key
 */
function sub_values(iterable $array, $valueKey, $indexKey = null): array
{
    $newa = [];

    if ($indexKey !== null) {
        foreach ($array as $value) {
            $newa[$value[$indexKey]] = $value[$valueKey];
        }
    } else {
        foreach ($array as $key => $value) {
            $newa[$key] = $value[$valueKey];
        }
    }

    return $newa;
}

/**
 * Groups all array values into sub arrays using the value at the specified index key as the groups key
 *
 * @param array $array The array of arrays to group
 * @param string $indexKey The array key to group the array by
 * @param string $defaultKey The default key to group by if specified key is not set
 */
function group_values(array $array, $indexKey, $defaultKey = null): array
{
    $newa = [];

    foreach($array as $value) {
        $key = null;

        if (is_array($value) && array_key_exists($indexKey, $value)) {
            $key = $value[$indexKey];
        } else {
            $key = $defaultKey;
        }

        if ($key !== null) {
            $newa[$key][] = $value;
        }
    }

    return $newa;
}
/**
 * Similiar to the default explode only it accepts an array of delimiters and has an option to keep the delimiter
 * in the returned array.
 *
 * @param mixed $delimiters The boundary string
 * @param string $string The input string
 * @param int $limit Limit the number of items to explode (see docs for default explode for specific behaviour)
 * @param bool $include_delimiter Set to true to include the delimiter values in the array
 */
function explode($delimiters, $string, $limit = false, $include_delimiter = false)
{
    if ($limit !== false && ($limit == 0 || $limit == 1)) {
        return [$string];
    }

    $exploded = [];

    $delimiters = ensure_array($delimiters, [null, '', false]);
    $delimiters = array_map('strval', $delimiters);
    $delimiters = array_unique($delimiters);
    if (!$delimiters) {
        $delimiters = [''];
    }
    // Reverse sort delimiters so longer ones get checked first
    // TODO: write custom sort so its based only on length
    rsort($delimiters);

    $start = 0;
    $len = pyncer_str_len($string);
    $count = 1;

    for ($i = 0; $i < $len; ++$i) {
        foreach ($delimiters as $delimiter) {
            $len_delimiter = pyncer_str_len($delimiter);
            if (pyncer_str_sub($string, $i, $len_delimiter) == $delimiter) {
                $exploded[] = pyncer_str_sub($string, $start, $i - $start);

                $start = $i + $len_delimiter;
                $i += $len_delimiter - 1;

                // Included delimiter does not effect limit
                if ($include_delimiter) {
                    $exploded[] = $delimiter;
                }

                ++$count;
                if ($limit !== false && $count == $limit) {
                    break 2;
                }

                break;
            }
        }
    }

    $exploded[] = pyncer_str_sub($string, $start);

    if ($limit < 0) {
        while ($limit < 0 && $exploded) {
            ++$limit;
            array_pop($exploded);
            if ($include_delimiter) {
                array_pop($exploded);
            }
        }
    }

    return $exploded;
}

function implode($glue, array $pieces, $count = false)
{
    if ($count === false) {
        return php_implode($glue, $pieces);
    }

    if ($count <= 0) {
        return '';
    }

    $pieces = array_values($pieces);

    $result = $pieces[0];

    $len = min(count($pieces), $count);
    for ($i = 1; $i < $len; ++$i) {
        $result .= $glue . $pieces[$i];
    }

    return $result;
}

/**
 * Gets the value from the specified array at the specified key map.
 *
 * @param mixed $array The array to get the value from
 * @param array $keys An array of sub keys where each key is a sub key of the previous key in the array
 */
function get_recursive($array, array $keys)
{
    $keys = array_reverse($keys);

    while (true) {
        $key = array_pop($keys);

        if (!isset($key)) {
            break;
        } elseif (is_array($array) && array_key_exists($key, $array)) {
            $array = $array[$key];
        } else {
            $array = null;
            break;
        }
    }

    return $array;
}
/**
 * Sets the value of the specified array at the specified key map.
 *
 * @param mixed $array The array to get the value from
 * @param array $keys An array of sub keys where each key is a sub key of the previous key in the array
 */
function set_recursive($array, array $keys, $value)
{
    if (!$keys) {
        return $value;
    }

    $key = array_shift($keys);

    if (!array_key_exists($key, $array) || !is_array($array[$key])) {
        $array[$key] = set_recursive([], $keys, $value);
    } else {
        $array[$key] = set_recursive($array[$key], $keys, $value);
    }

    return $array;
}
/**
 * Pushes a value to the specified array at the specified key map.
 *
 * @param mixed $array The array to get the value from
 * @param array $keys An array of sub keys where each key is a sub key of the previous key in the array
 */
function push_recursive($array, array $keys, $value)
{
    if (!$keys) {
        $array[] = $value;
        return $array;
    }

    $key = array_shift($keys);

    if (!array_key_exists($key, $array) || !is_array($array[$key])) {
        $array[$key] = push_recursive([], $keys, $value);
    } else {
        $array[$key] = push_recursive($array[$key], $keys, $value);
    }

    return $array;
}

/**
 * Filters out all values with a key that doesn't start with a specifed prefix value.
 *
 * @param array $array The array to filter
 * @param string $prefix A prefix value to filter
 * @param bool $remove_prefix Remove the prefix from the resuling array keys
 */
function filter_prefixed_keys(array $array, $prefix, $removePrefix = false) {
    $newa = [];

    $len = strlen($prefix);

    foreach ($array as $key => $value) {
        if (substr($key, 0, $len) == $prefix) {
            if ($removePrefix) {
                $newa[substr($key, $len)] = $value;
            } else {
                $newa[$key] = $value;
            }
        }
    }

    return $newa;
}

/**
 * Filters out all values with a key that doesn't start with a specifed filter value.
 *
 * @param array $array The array to filter
 * @param mixed $filters One or more filter values to filter
 * @param string $separator A string value that separates the filter string from the rest of the string
 */
function filter(array $array, $filters, $separator = '_')
{
    $newa = [];

    $filters = ensure_array($filters, [null, '', false]);
    $filters = array_map('strval', $filters);
    $filters = array_unique($filters);

    foreach ($array as $key => $value) {
        foreach ($filters as $f) {
            $len = strlen($f) + strlen($separator);
            if ($key == $f || substr($key, 0, $len) == $f . $separator) {
                $newa[$key] = $value;
                break;
            }
        }
    }

    return $newa;
}
/**
 * Filters out all values with a key that doesn't start with a specifed filter value. The filter value and
 * separator will be removed from the key.
 *
 * @param array $array The array to filter
 * @param mixed $filters One or more filter values to filter
 * @param string $separator A string value that separates the filter string from the rest of the string
 */
function filter_out_from_key(array $array, $filters, $separator = '_')
{
    $newa = [];

    $filters = ensure_array($filters, [null, '', false]);
    $filters = array_map('strval', $filters);
    $filters = array_unique($filters);

    foreach ($array as $key => $value) {
        foreach ($filters as $f) {
            $len = strlen($f) + strlen($separator);
            if (substr($key, 0, $len) == $f . $separator) {
                $newa[substr($key, $len)] = $value;
                break;
            }
        }
    }

    return $newa;
}
/**
 * Filters out all values with a key that don't start with a specifed filter value. The filter value will
 * be used as the key for a new sub array that contains all values with a key that starts with that filter value.
 *
 * @param array $array The array to filter
 * @param mixed $filters One or more filter values to filter
 * @param string $separator A string value that separates the filter string from the rest of the string
 */
function filter_into_sub_key(array $array, $filters, $separator = '_')
{
    $newa = [];

    $filters = ensure_array($filters, [null, '', false]);
    $filters = array_map('strval', $filters);
    $filters = array_unique($filters);

    foreach ($array as $key => $value) {
        foreach ($filters as $f) {
            $len = strlen($f) + strlen($separator);
            if (substr($key, 0, $len) == $f . $separator) {
                $newa[$f][substr($key, $len)] = $value;
                break;
            }
        }
    }

    return $newa;
}

function sort_keys(array $array, array $keys)
{
    $newa = [];

    foreach ($keys as $value) {
        if (array_key_exists($value, $array)) {
            $newa[$value] = $array[$value];
            unset($array[$value]);
        }
    }

    // Remaining items
    foreach ($array as $key => $value) {
        $newa[$key] = $value;
    }

    return $newa;
}
function sort_values(array $array, array $values, $strict = false)
{
    $newa = [];

    foreach ($values as $value) {
        $search = array_search($value, $array, $strict);
        if ($search !== false) {
            $newa[$search] = $array[$search];
            unset($array[$search]);
        }
    }

    // Remaining items
    foreach ($array as $key => $value) {
        $newa[$key] = $value;
    }

    return $newa;
}

function prefix_keys(array $array, $prefix)
{
    $newa = [];

    foreach ($array as $key => $value) {
        $newa[$prefix . $key] = $value;
    }

    return $newa;
}
