<?php
namespace Pyncer\Array;

use Pyncer\Exception\InvalidArgumentException;

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
use function Pyncer\nullify as pyncer_nullify;
use function Pyncer\String\len as pyncer_str_len;
use function Pyncer\String\sub as pyncer_str_sub;
use function rsort;
use function strlen;
use function substr;
use Traversable;

/**
 * Returns $defaultValue if $array is null or empty.
 *
 * @param null|array<mixed> $value The value to nullify.
 * @param mixed $defaultValue The value to return if nullifiable.
 * @return mixed The resulting value.
 */
function nullify(?array $value, mixed $defaultValue = null): mixed
{
    if ($value === null || $value === []) {
        return $defaultValue;
    }

    return $value;
}

/**
 * Ensures that $value is list array.
 *
 * If $value exists in $empty, then an empty array will be returned.
 *
 * @param mixed $value The value to ensure is an array.
 * @param array<mixed> $empty List of values that would represent an empty
 *      array.
 * @return array<int|string, mixed> The resulting array.
 */
function ensure_array(mixed $value, array $empty = []): array
{
    if ($value instanceof Traversable) {
        $value = iterator_to_array($value, false);
    }

    if (in_array($value, $empty, true)) {
        return [];
    }

    if (is_array($value)) {
        return array_values($value);
    }

    return [$value];
}

/**
 * Ensures that the array $array contains all the keys in $keys.
 *
 * If a key is not found, it will be set to the value of $default.
 *
 * @param array<int|string, mixed> $array The array in which to ensure the
 *      keys exist in.
 * @param array<int|string> $keys An array of keys.
 * @param mixed $defaultValue The default value to use for not found keys.
 * @return array<int|string, mixed> The resulting array.
 */
function ensure_keys(
    array $array,
    array $keys,
    mixed $defaultValue = null
): array
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $defaultValue;
        }
    }

    return $array;
}

/**
 * Ensures that the array $array contains all the keys in the array $values.
 *
 * If a key is not found, it will be set to the corresponding value in $values.
 *
 * @param array<int|string, mixed> $array An array in which to ensure has
 *      certain values.
 * @param array<int|string, mixed> $values Values to ensure exist in the array.
 * @return array<int|string, mixed> The resulting array.
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
 * Ensures that the array $array contains all the keys in the array $values and
 * sub array values.
 *
 * @param array<int|string, mixed> $array An array in which to ensure has
 *      certain values.
 * @param array<int|string, mixed> $values Values to ensure exist in the array.
 * @return array<int|string, mixed> The resulting array.
 */
function ensure_values_recursive(array $array, array $values): array
{
    foreach ($values as $key => $value) {
        if (!array_key_exists($key, $array)) {
            $array[$key] = $value;
            continue;
        }

        if (is_array($array[$key]) && is_array($value)) {
            $array[$key] = ensure_values_recursive($array[$key], $value);
        }
    }

    return $array;
}

/**
 * Gets a new array with only the keys specified in $keys.
 *
 * @template T
 * @param array<int|string, T> $array Array to intersect.
 * @param array<int|string> $keys Keys to intersect with.
 * @return array<int|string, T> The resulting new array.
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

/**
 * Gets a new array without any of the keys specified in $keys.
 *
 * @template T
 * @param array<int|string, T> $array Array to diff.
 * @param array<int|string> $keys Keys to diff with.
 * @return array<int|string, T> The resulting new array.
 */
function diff_keys(array $array, array $keys): array
{
    $newa = [];

    foreach ($array as $key => $value) {
        $search = array_search($key, $keys);

        if ($search === false) {
            $newa[$key] = $value;
        }
    }

    return $newa;
}

/**
 * Gets a new list array of all values in all $arrays arrays.
 *
 * @param iterable<mixed> ...$arrays Arrays to merge.
 * @return array<mixed> The resulting new array.
 */
function merge_safe(iterable ...$arrays): array
{
    $newa = [];

    foreach ($arrays as $array) {
        foreach ($array as $value) {
            $newa[] = $value;
        }
    }

    return $newa;
}

/**
 * Recursively merges two arrays.
 *
 * Unlike array_merge_recursive, if an array has list items, they will be
 * merged the same way as associative items.
 *
 * @param iterable<int|string, mixed> ...$arrays Arrays to merge.
 * @return array<int|string, mixed> The resulting new array.
 */
function merge_recursive(iterable ...$arrays): array
{
    $newa = [];

    foreach ($arrays as $value) {
        foreach ($value as $key => $value2) {
            if (is_array($value2) &&
                array_key_exists($key, $newa) &&
                is_array($newa[$key])
            ) {
                $newa[$key] = merge_recursive($newa[$key], $value2);
            } else {
                $newa[$key] = $value2;
            }
        }
    }

    return $newa;
}

/**
 * Merges all values that don't appear in multiple arrays.
 *
 * @param array<int|string, mixed> ...$arrays Arrays to merge.
 * @return array<int|string, mixed> The resulting new array.
 */
function merge_diff(array ...$arrays): array
{
    $intersected = array_intersect(...$arrays);

    $newa = [];

    foreach ($arrays as $array) {
         $newa = array_merge($newa, array_diff($array, $intersected));
    }

    return $newa;
}

/**
 * Merges one or more arrays together. Any duplicate entries will be joined
 * together with $separator.
 *
 * @param string $separator
 * @param iterable<int|string, mixed> ...$arrays
 * @return array<int|string, mixed> The resulting new array.
 */
function merge_implode(string $separator, iterable ...$arrays): array
{
    $newa = [];

    foreach ($arrays as $array) {
        foreach ($array as $key => $value) {
            if (array_key_exists($key, $newa)) {
                $newa[$key] .= $separator . strval($value);
            } else {
                $newa[$key] = $value;
            }
        }
    }

    return $newa;
}

/**
 * Removes empty values from $array.
 *
 * Empty values consists of null, false, 0, 0.0, '', and [].
 *
 * @template T
 * @param iterable<int|string, T> $array The array to remove empty values from.
 * @return ($array is list<T> ? list<T> : array<int|string, T>) The resulting new array.
 */
function unset_empty(iterable $array): array
{
    $newa = [];

    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, true);
    }

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

/**
 * Removes null values from $array.
 *
 * @template T
 * @param iterable<int|string, T> $array The array to remove null values from.
 * @return array<int|string, T> The resulting new array.
 */
function unset_null(iterable $array): array
{
    $newa = [];

    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, true);
    }

    foreach ($array as $key => $value) {
        if ($value !== null) {
            $newa[$key] = $value;
        }
    }

    return $newa;
}

/**
 * Unsets the specified keys from $array.
 *
 * @template T
 * @param iterable<int|string, T> $array The array to unset values from.
 * @param array<int|string> $keys An array of keys to unset.
 * @return array<int|string, T> The resulting new array.
 */
function unset_keys(iterable $array, array $keys): array
{
    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, true);
    }

    foreach ($keys as $value) {
        unset($array[$value]);
    }

    return $array;
}

/**
 * Removes the specified values from $array.
 *
 * Similar to array_diff, but does not reorder the array.
 *
 * @template T
 * @param iterable<int|string, T> $array The array to remove values from.
 * @param iterable<int|string, mixed> $values The values to remove.
 * @return array<int|string, T> The resulting new array.
 */
function unset_values(iterable $array, iterable $values): array
{
    $newa = [];

    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, true);
    }

    if ($values instanceof Traversable) {
        $values = iterator_to_array($values, false);
    }

    foreach ($array as $key => $value) {
        if (!in_array($value, $values, true)) {
            $newa[$key] = $value;
        }
    }

    return $newa;
}

/**
 * @param iterable<int|string, mixed> $array
 * @return array<int|string, null>
 */
function null_values(iterable $array): array
{
    $new = [];

    foreach ($array as $key => $value) {
        $new[$key] = null;
    }

    return $new;
}

/**
 * @param iterable<int|string, mixed> $array
 * @param mixed $defaultValue
 * @return array<int|string, mixed>
 */
function nullify_values(iterable $array, mixed $defaultValue = null): array
{
    $new = [];

    foreach ($array as $key => $value) {
        $new[$key] = pyncer_nullify($value, $defaultValue);
    }

    return $new;
}

/**
 * @param non-empty-string $separator
 * @param string $string
 * @return array<int, string>
 */
function data_explode(string $separator, string $string): array
{
    $string = php_explode($separator, $string);
    $string = array_map(trim(...), $string);
    $string = unset_empty($string);
    return $string;
}

/**
 * @param string $separator
 * @param iterable<int|string, mixed> $array
 * @return string
 */
function data_implode(string $separator, iterable $array): string
{
    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, false);
    }

    $array = array_map(strval(...), $array);
    $array = array_map(trim(...), $array);
    $array = unset_empty($array);
    $array = php_implode($separator, $array);

    return $array;
}

/**
 * Counts the number of numeric keys in the array.
 *
 * @param array<int|string, mixed> $array The array to count.
 * @return int The number of numeric indexes.
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
 * @param array<int|string, mixed> $array The array to ensure has the
 *      speicified number of values
 * @param int $count The number of values to ensure the array contains
 * @param mixed $defaultValue The default value to fill any additional
 *      values with.
 * @return array<int|string, mixed>
 */
function ensure_index_count(
    array $array,
    int $count,
    mixed $defaultValue = null
): array
{
    $start = index_count($array);
    $len = $count - $start;

    if ($len <= 0) {
        return $array;
    }

    for ($i = 0; $i < $len; ++$i) {
        $array[] = $defaultValue;
    }

    return $array;
}

/**
 * Rebuild the array index so all numeric keys are in a sequence
 * starting at 0 and non numeric keys are preserved.
 *
 * @template T
 * @param array<int|string, T> $array The array to rebuild.
 * @return array<int|string, T> The reordered array.
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

/**
 * @template T
 * @param array<int|string, T> $array
 * @return array<int|string, T>
 */
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

/**
 * @template T
 * @param array<int|string, T> $array
 * @return array<int|string, T>
 */
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

/**
 * Determines if the array has compositions.
 *
 * An array is considered to have compositions if it contains any value that is
 * not null or a scalar.
 *
 * @param array<int|string, mixed> $array The array to check.
 * @return bool True if the array has compositions, otherwise false.
 */
function has_compositions(array $array): bool
{
    foreach ($array as $value) {
        if (!is_scalar($value) && $value !== null) {
            return true;
        }
    }

    return false;
}

/**
 * Gets the first key in the array.
 *
 * @param array<int|string, mixed> $array The array to get the first key from.
 * @return null|int|string The first key in the array.
 */
function first_key(array $array): null|int|string
{
    if (!$array) {
        return null;
    }

    return array_keys($array)[0];
}

/**
 * Gets the first value in the array.
 *
 * @template T
 * @param array<int|string, T> $array The array to get the first value from.
 * @return null|T The first value of the array of null if the array is empty.
 */
function first_value(array $array): mixed
{
    if (!$array) {
        return null;
    }

    return array_shift($array);
}

/**
 * Gets the last key in the array.
 *
 * @param array<int|string, mixed> $array The array to get the last key from.
 * @return null|int|string The last key in the array.
 */
function last_key(array $array): null|int|string
{
    if (!$array) {
        return null;
    }

    $keys = array_keys($array);
    return $keys[count($keys) - 1];
}

/**
 * Gets the last value in the array.
 *
 * @template T
 * @param array<int|string, T> $array The array to get the last value from.
 * @return null|T The last value of the array of null if the array is empty.
 */
function last_value(array $array): mixed
{
    if (!$array) {
        return null;
    }

    return array_pop($array);
}

/**
 * Gets a new array of all values at the specified key $valueKey in sub array
 * items.
 *
 * @param array<int|string, array<int|string, mixed>> $array Array to get values from.
 * @param int|string $valueKey Key to use as sub array value.
 * @param null|int|string $indexKey Key to use as sub array key.
 * @param mixed $defaultValue The default value to use if there is no value at
 *      $valueKey.
 * @return array<int|string, mixed> The resulting array.
 */
function sub_values(
    iterable $array,
    int|string $valueKey,
    null|int|string $indexKey = null,
    mixed $defaultValue = null,
): array
{
    $newa = [];

    foreach ($array as $key => $value) {
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                'Array must be an array of arrays.'
            );
        }

        if ($indexKey !== null) {
            if (!array_key_exists($indexKey, $value)) {
                throw new InvalidArgumentException(
                    'Sub arrays of array must all have the specified $indexKey.'
                );
            }

            $newa[$value[$indexKey]] = $value[$valueKey] ?? $defaultValue;
        } else{
            $newa[$key] = $value[$valueKey] ?? $defaultValue;
        }
    }

    return $newa;
}

/**
 * Groups all array values in $array into sub arrays using the value at key
 * $groupKey as the groups key.
 *
 * If a value is not an array, it will be grouped under $defaultKey.
 *
 * @param array<int|string, mixed> $array The array of arrays to group.
 * @param int|string $groupKey The array key to group the array by.
 * @param int|string $defaultKey The default key to group by if specified key
 *      is not set.
 * @return array<int|string, mixed> The resulting new array.
 */
function group_values(
    array $array,
    int|string $groupKey,
    int|string $defaultKey = null
): array
{
    $newa = [];

    foreach ($array as $value) {
        $key = null;

        if (is_array($value) && array_key_exists($groupKey, $value)) {
            $key = $value[$groupKey];
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
 * Similiar to the default explode only it accepts an array of separators and
 * has an option to keep the separator in the returned array.
 *
 * @param string|array<string> $separators The boundary strings.
 * @param string $string The input string.
 * @param int $limit Limit the number of items to explode.
 * @param bool $includeSeparator When true the separator values will be
 *      included in the array.
 * @return array<string> The resulting array.
 */
function explode(
    string|array $separators,
    string $string,
    ?int $limit = null,
    bool $includeSeparator = false
): array
{
    if ($limit !== null && ($limit == 0 || $limit == 1)) {
        return [$string];
    }

    $exploded = [];

    $separators = ensure_array($separators, [null, '', false]);
    $separators = array_map('strval', $separators);
    $separators = array_unique($separators);

    if (!$separators) {
        $separators = [''];
    }

    // Reverse sort separators so longer ones get checked first
    // TODO: write custom sort so its based only on length
    rsort($separators);

    $start = 0;
    $len = pyncer_str_len($string);
    $count = 1;

    for ($i = 0; $i < $len; ++$i) {
        foreach ($separators as $separator) {
            $len_separator = pyncer_str_len($separator);
            if (pyncer_str_sub($string, $i, $len_separator) == $separator) {
                $exploded[] = pyncer_str_sub($string, $start, $i - $start);

                $start = $i + $len_separator;
                $i += $len_separator - 1;

                // Included separator does not effect limit
                if ($includeSeparator) {
                    $exploded[] = $separator;
                }

                ++$count;
                if ($limit !== null && $count == $limit) {
                    break 2;
                }

                break;
            }
        }
    }

    $exploded[] = pyncer_str_sub($string, $start);

    if ($limit !== null && $limit < 0) {
        while ($limit < 0 && $exploded) {
            ++$limit;
            array_pop($exploded);
            if ($includeSeparator) {
                array_pop($exploded);
            }
        }
    }

    return $exploded;
}

/**
 * @param string $separator
 * @param array<string> $array
 * @param null|int $count
 * @return string The resulting string.
 */
function implode(string $separator, array $array, ?int $count = null): string
{
    if ($count === null) {
        return php_implode($separator, $array);
    }

    if ($count <= 0) {
        return '';
    }

    $array = array_values($array);

    $result = $array[0];

    $len = min(count($array), $count);
    for ($i = 1; $i < $len; ++$i) {
        $result .= $separator . $array[$i];
    }

    return $result;
}

/**
 * Gets $value from $array at the key map specified by $keys.
 *
 * @param array<int|string, mixed> $array The array to get the value from.
 * @param array<int|string> $keys An array of keys where each key is a sub key of the
 *      previous key in the array.
 * @return mixed The value.
 */
function get_recursive($array, array $keys): mixed
{
    $keys = array_reverse($keys);

    while (true) {
        $key = array_pop($keys);

        if ($key === null) {
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
 * Sets $value to $array at the key map specified by $keys.
 *
 * @param array<int|string, mixed> $array The array to set the value to
 * @param array<int|string> $keys An array of keys where each key is a sub key of the
 *      previous key in the array.
 * @param mixed $value The value to set.
 * @return array<int|string, mixed> The resulting array.
 */
function set_recursive(array $array, array $keys, mixed $value): array
{
    if (!$keys) {
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                'Value must be an array if no keys specified.'
            );
        }

        return $value;
    }

    $key = array_shift($keys);

    if (!$keys) {
        $array[$key] = $value;
    } elseif (!array_key_exists($key, $array) || !is_array($array[$key])) {
        $array[$key] = set_recursive([], $keys, $value);
    } else {
        $array[$key] = set_recursive($array[$key], $keys, $value);
    }

    return $array;
}

/**
 * Pushes $value to $array at the key map specified by $keys.
 *
 * @param array<int|string, mixed> $array The array to push the value to.
 * @param array<int|string> $keys An array of keys where each key is a sub key of the
 *      previous key in the array.
 * @param mixed $value The value to push.
 * @return array<int|string, mixed> The resulting array.
 */
function push_recursive(array $array, array $keys, mixed $value): array
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
 * Filters out all values in $array with a key that doesn't start with $prefix.
 *
 * @template T
 * @param array<string, T> $array The array to filter
 * @param string $prefix A prefix value to filter
 * @param bool $removePrefix Remove the prefix from the resuling array keys
 * @return array<string, T> The resulting new array.
 */
function filter_prefixed_keys(
    array $array,
    string $prefix,
    bool $removePrefix = false
) : array
{
    $newa = [];

    $len = strlen($prefix);

    foreach ($array as $key => $value) {
        if (str_starts_with($key, $prefix)) {
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
 * Filters out all values in $array with a key that doesn't start with a value
 * in $filters.
 *
 * @template T
 * @param array<string, T> $array The array to filter.
 * @param string|array<string> $filters One or more filter values to filter.
 * @param string $separator A string value that separates the filter string
 *      from the rest of the string.
 * @return array<string, T> The resulting new array.
 */
function filter(array $array, mixed $filters, string $separator = '_'): array
{
    $newa = [];

    $filters = ensure_array($filters, [null, '', false]);
    $filters = array_map('strval', $filters);
    $filters = array_unique($filters);

    foreach ($array as $key => $value) {
        foreach ($filters as $filter) {
            if ($key == $filter ||
                str_starts_with($key, $filter . $separator)
            ) {
                $newa[$key] = $value;
                break;
            }
        }
    }

    return $newa;
}

/**
 * Filters out all values in $array with a key that doesn't start with a value
 * in $filters.
 *
 * The filter and separator will be removed from the resulting arrays keys.
 *
 * @template T
 * @param array<string, T> $array The array to filter.
 * @param string|array<string> $filters One or more filter values to filter.
 * @param string $separator A string value that separates the filter string
 *      from the rest of the string.
 * @return array<string, T> The resulting new array.
 */
function filter_out_from_key(
    array $array,
    mixed $filters,
    string $separator = '_'
): array
{
    $newa = [];

    $filters = ensure_array($filters, [null, '', false]);
    $filters = array_map('strval', $filters);
    $filters = array_unique($filters);

    foreach ($array as $key => $value) {
        foreach ($filters as $filter) {
            if (str_starts_with($key, $filter . $separator)) {
                $len = strlen($filter . $separator);
                $newa[substr($key, $len)] = $value;
                break;
            }
        }
    }

    return $newa;
}

/**
 * Filters out all values in $array with a key that doesn't start with a value
 * in $filters.
 *
 * The resulting array will group each item under an array with the matching
 * filter.
 *
 * @template T
 * @param array<string, T> $array The array to filter.
 * @param string|array<string> $filters One or more filter values to filter.
 * @param string $separator A string value that separates the filter string
 *      from the rest of the string.
 * @return array<string, array<string, T>> The resulting new array.
 */
function filter_into_sub_key(
    array $array,
    string|array $filters,
    string $separator = '_'
): array
{
    $newa = [];

    $filters = ensure_array($filters, [null, '', false]);
    $filters = array_map('strval', $filters);
    $filters = array_unique($filters);

    foreach ($array as $key => $value) {
        foreach ($filters as $filter) {
            if (str_starts_with($key, $filter . $separator)) {
                $len = strlen($filter . $separator);
                $newa[$filter][substr($key, $len)] = $value;
                break;
            }
        }
    }

    return $newa;
}

/**
 * @template T
 * @param iterable<int|string, T> $array
 * @param iterable<int|string> $keys
 * @return array<int|string, T> The resulting sorted array.
 */
function sort_keys(iterable $array, iterable $keys): array
{
    $newa = [];

    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, true);
    }

    if ($keys instanceof Traversable) {
        $keys = iterator_to_array($keys, false);
    }

    foreach ($keys as $key) {
        if (array_key_exists($key, $array)) {
            $newa[$key] = $array[$key];
            unset($array[$key]);
        }
    }

    // Remaining items
    foreach ($array as $key => $value) {
        $newa[$key] = $value;
    }

    return $newa;
}

/**
 * @template T
 * @param iterable<int|string, T> $array
 * @param iterable<T> $values
 * @param bool $strict
 * @return array<int|string, T> The resulting sorted array.
 */
function sort_values(
    iterable $array,
    iterable $values,
    bool $strict = false
): array
{
    $newa = [];

    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, true);
    }

    if ($values instanceof Traversable) {
        $values = iterator_to_array($values, false);
    }

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

/**
 * @template T
 * @param iterable<int|string, T> $array
 * @param string $prefix
 * @return array<int|string, T>
 */
function prefix_keys(iterable $array, string $prefix): array
{
    $newa = [];

    if ($array instanceof Traversable) {
        $array = iterator_to_array($array, true);
    }

    foreach ($array as $key => $value) {
        $newa[$prefix . $key] = $value;
    }

    return $newa;
}
