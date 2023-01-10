<?php
namespace Pyncer\Http;

use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\RuntimeException;
use Traversable;

use function array_merge;
use function array_walk_recursive;
use function base64_encode as php_base64_encode;
use function base64_decode as php_base64_decode;
use function count;
use function explode;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function parse_str;
use function preg_replace_callback;
use function Pyncer\Array\merge_recursive as pyncer_merge_recursive;
use function Pyncer\String\ltrim_string as pyncer_ltrim_string;
use function Pyncer\String\pos_array as pyncer_str_pos_array;
use function Pyncer\String\rtrim_string as pyncer_rtrim_string;
use function Pyncer\String\sub as pyncer_str_sub;
use function rawurlencode;
use function rawurldecode;
use function rtrim;
use function str_contains;
use function str_pad;
use function str_replace;
use function strlen;
use function strpos;
use function strstr;
use function strval;
use function substr;
use function trim;

use const DIRECTORY_SEPARATOR as DS;

function clean_url(string $url): string
{
    if (!str_contains($url, '://')) {
        throw new InvalidArgumentException('Invalid url. (' . $url . ')');
    }

    $parts = explode('?', $url, 2);

    $parts[0] = clean_path($parts[0]);

    // Ensure trailing / if no path
    if (substr_count($parts[0], '/') === 2) {
        $parts[0] .= '/';
    }

    return implode('?', $parts);
}

function clean_path(string $path): string
{
    if ($path === '') {
        return '';
    }

    // Ensure URL path separators
    $path = str_replace(['\\', DS], '/', $path);

    // Ensure start slash if no protocol
    if (strpos($path, '://') === false && substr($path, 0, 1) !== '/') {
        $path = '/' . $path;
    }

    // Remove ending slash
    $path = rtrim($path, '/');

    return $path;
}

function ltrim_path(string $path, string $trim): string
{
    $trim = trim($trim, '/');

    if ($trim === '') {
        return '/' . trim($path, '/');
    }

    $path = '/' . trim($path, '/') . '/';
    $trim = '/' . $trim . '/';

    return rtrim('/' . pyncer_ltrim_string($path, $trim, true), '/');
}
function rtrim_path(string $path, string $trim): string
{
    $trim = trim($trim, '/');

    if ($trim === '') {
        return '/' . trim($path, '/');
    }

    $path = '/' . trim($path, '/') . '/';
    $trim = '/' . $trim . '/';

    return rtrim(pyncer_rtrim_string($path, $trim, true), '/');
}

/**
 * Parses a url query into its individual array elements.
 *
 * @param string $query The query string to parse.
 * @return array<int|string, mixed> An array of query key value pairs.
 */
function parse_url_query(string $query): array
{
    $query = ltrim($query, '?');
    parse_str($query, $parsed);
    return $parsed;
}

/**
 * Merges to url queries together.
 *
 * @param string|iterable<int|string, mixed> ...$queries An array of queries to merge.
 * @return array<int|string, mixed> The merged queries.
 */
function merge_url_queries(string|iterable ...$queries): array
{
    $q = [];

    foreach ($queries as $query) {
        if ($query instanceof Traversable) {
            $query = iterator_to_array($query, true);
        }

        if (is_string($query)) {
            $query = parse_url_query($query);
        } else {
            $query = build_url_query($query);
            $query = parse_url_query($query);
        }

        $q = pyncer_merge_recursive($q, $query);
    }

    return $q;
}

/**
 * Builds an url query string from key value pairs.
 *
 * @param iterable<int|string, mixed> $query An iterable object of key value pairs.
 * @param int $encodeMethod The encode method to use to encode values.
 * @return string The built query string.
 */
function build_url_query(iterable $query, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    if ($query instanceof Traversable) {
        $query = iterator_to_array($query, true);
    }

    $buildQuery = function(
        $query,
        $prefix = null,
    ) use (
        &$buildQuery,
        $encodeMethod,
    ) {
        if (!is_array($query)) {
            return $query;
        }

        $queryParts = [];

        foreach ($query as $key => $value) {
            $newPrefix = ($prefix ? $prefix . '[' . $key . ']' : $key);

            if (is_array($value)) {
                $queryParts[] = $buildQuery($value, $newPrefix);
                continue;
            }

            $key = encode_url($newPrefix, $encodeMethod);

            if ($value === null) {
                $queryParts[] = $key;
                continue;
            }

            if ($value === false) {
                $value = '0';
            } else {
                $value = strval($value);
            }

            $value = encode_url($value, $encodeMethod);

            $queryParts[] = $key . '=' . $value;
        }

        return implode('&', $queryParts);
    };

    return $buildQuery($query);
}

function relative_url(string $url, ?string $to = null): string
{
    if ($to !== null) {
        // Remove protocal if any
        $pos = strpos($to, '//');
        if ($pos === false) {
            $website_domain = $to;
        } else {
            $website_domain = substr($to, $pos + 2);
        }
        $website_domain = rtrim($website_domain, '/');

        // Remove protocal if any
        $pos = strpos($url, '//');
        if ($pos === false) {
            $url_domain = $url;
        } else {
            $url_domain = substr($url, $pos + 2);
        }
        $protocal = ($url_domain !== $url);

        // Remove www. before comparing
        if (substr($website_domain, 0, 4) == 'www.') {
            $website_domain = substr($website_domain, 4);
        }

        if (substr($url_domain, 0, 4) == 'www.') {
            $url_domain = substr($url_domain, 4);
        }

        // If on same host, convert to relative
        if (pyncer_ltrim_string($url_domain, $website_domain) !== $url_domain) {
            $pos = strpos($url, $website_domain) + strlen($website_domain);
            $char = substr($url, $pos, 1);
            // Make sure end of domain is a separator character
            // example.com/ vs example.comm/
            if (!$char || in_array($char, ['/', '?', '#'])) {
                $url = substr($url, $pos);

                if (!$url) {
                    $url = '/';
                }
            }
        } elseif (!$protocal &&
            !in_array(substr($url, 0, 1), ['/', '?', '#'])
        ) {
            // Determine if there is a domain present
            $pos = pyncer_str_pos_array($url, ['/', '?', '#']);
            if ($pos !== false) {
                $url_domain = pyncer_str_sub($url, 0, $pos['index']);
            } else {
                $url_domain = $url;
            }

            // If there is a dot, then there is a domain
            if (str_contains($url_domain, '.')) {
                $url = 'http://' . $url;
            } else {
                $url = '/' . $url;
            }
        }

        return $url;
    }

    $offset = strpos($url, '://');
    if ($offset !== false) {
        $offset += 3;
    } else {
        $offset = 0;
    }

    $pos = pyncer_str_pos_array($url, ['/', '?', '#'], $offset);

    // Nothing after the domain
    if ($pos === false) {
        return '/';
    }

    $url = pyncer_str_sub($url, $pos['index']);
    if (!$url) {
        $url = '/';
    }

    return $url;
}
function absolute_url(string $url, string $to): string
{
    if (str_contains($url, '://')) {
        return $url;
    }

    return rtrim($to, '/') . '/' . ltrim($url, '/');
}

function url_equals(string $url1, string $url2): bool
{
    // Parse the URLs into their component parts
    $parts1 = parse_url($url1);
    $parts2 = parse_url($url2);

    $host1 = $parts1['host'] ?? null;
    $host2 = $parts2['host'] ?? null;

    // Check if the host and path are equal
    if ($host1 !== $host2) {
        return false;
    }

    $path1 = rtrim($parts1['path'] ?? '', '/');
    $path2 = rtrim($parts2['path'] ?? '', '/');

    if ($path1 !== $path2) {
        return false;
    }

    // Parse the query strings into arrays of parameters
    parse_str($parts1['query'] ?? '', $query1);
    parse_str($parts2['query'] ?? '', $query2);

    $ksortRecursive = function (&$array) use(&$ksortRecursive)
    {
        if (!is_array($array)) {
            return false;
        }

        ksort($array);

        foreach ($array as &$value) {
            $ksortRecursive($value);
        }

        return true;
    };

    // Sort the arrays of parameters
    $ksortRecursive($query1);
    $ksortRecursive($query2);

    // Check if the sorted arrays of parameters are equal
    if ($query1 !== $query2) {
        return false;
    }

    return true;
}

function encode_url(string $url, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $url = rawurlencode($url);

    if ($encodeMethod === PHP_QUERY_RFC1738) {
        $url = str_replace('%20', '+', $url);
    }

    return $url;
}

function decode_url(string $url, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    if ($encodeMethod === PHP_QUERY_RFC1738) {
        $url = str_replace('+', ' ', $url);
    }

    return rawurldecode($url);
}

function encode_url_path(string $path, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $result = preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/]+|%(?![A-Fa-f0-9]{2}))/',
        function ($match) use ($encodeMethod) {
            return encode_url($match[0], $encodeMethod);
        },
        $path
    );

    if ($result === null) {
        throw new RuntimeException('URL path could not be encoded.');
    }

    return $result;
}

function encode_url_user_info(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $result = preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=]+|%(?![A-Fa-f0-9]{2}))/u',
        function ($match) use ($encodeMethod) {
            return encode_url($match[0], $encodeMethod);
        },
        $value
    );

    if ($result === null) {
        throw new RuntimeException('URL user info could not be encoded.');
    }

    return $result;
}
function encode_url_query(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $result = preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
        function ($match) use ($encodeMethod) {
            return encode_url($match[0], $encodeMethod);
        },
        $value
    );

    if ($result === null) {
        throw new RuntimeException('URL query could not be encoded.');
    }

    return $result;
}
function encode_url_fragment(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return encode_url_query($value, $encodeMethod);
}

function base64_encode(string $data): string
{
    return rtrim(strtr(php_base64_encode($data), '+/', '-_'), '=');
}
function base64_decode(string $data): string
{
    return php_base64_decode(str_pad(
        strtr($data, '-_', '+/'),
        strlen($data) % 4,
        '=',
        STR_PAD_RIGHT
    ));
}
