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
use function parse_url;
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

function clean_uri(string $uri): string
{
    if (!str_contains($uri, '://')) {
        throw new InvalidArgumentException('Invalid uri. (' . $uri . ')');
    }

    $parts = explode('?', $uri, 2);

    $parts[0] = clean_path($parts[0]);

    // Ensure trailing / if no path and a query
    if (($parts[1] ?? null) !== null && substr_count($parts[0], '/') === 2) {
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
 * Parses a uri query into its individual array elements.
 *
 * @param string $query The query string to parse.
 * @return array<int|string, mixed> An array of query key value pairs.
 */
function parse_uri_query(string $query): array
{
    $query = ltrim($query, '?');
    parse_str($query, $parsed);
    return $parsed;
}

/**
 * Merges to uri queries together.
 *
 * @param string|iterable<int|string, mixed> ...$queries An array of queries to merge.
 * @return array<int|string, mixed> The merged queries.
 */
function merge_uri_queries(string|iterable ...$queries): array
{
    $q = [];

    foreach ($queries as $query) {
        if ($query instanceof Traversable) {
            $query = iterator_to_array($query, true);
        }

        if (is_string($query)) {
            $query = parse_uri_query($query);
        } else {
            $query = build_uri_query($query);
            $query = parse_uri_query($query);
        }

        $q = pyncer_merge_recursive($q, $query);
    }

    return $q;
}

/**
 * Builds an uri query string from key value pairs.
 *
 * @param iterable<int|string, mixed> $query An iterable object of key value pairs.
 * @param int $encodeMethod The encode method to use to encode values.
 * @return string The built query string.
 */
function build_uri_query(iterable $query, int $encodeMethod = PHP_QUERY_RFC3986): string
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

            $key = encode_uri($newPrefix, $encodeMethod);

            if ($value === null) {
                $queryParts[] = $key;
                continue;
            }

            if ($value === false) {
                $value = '0';
            } else {
                $value = strval($value);
            }

            $value = encode_uri($value, $encodeMethod);

            $queryParts[] = $key . '=' . $value;
        }

        return implode('&', $queryParts);
    };

    return $buildQuery($query);
}

function relative_uri(string $uri, ?string $to = null): string
{
    if ($to !== null) {
        // Remove protocal if any
        $pos = strpos($to, '//');
        if ($pos === false) {
            $website_host = $to;
        } else {
            $website_host = substr($to, $pos + 2);
        }
        $website_host = rtrim($website_host, '/');

        // Remove protocal if any
        $pos = strpos($uri, '//');
        if ($pos === false) {
            $uri_host = $uri;
        } else {
            $uri_host = substr($uri, $pos + 2);
        }
        $protocal = ($uri_host !== $uri);

        // Remove www. before comparing
        if (substr($website_host, 0, 4) == 'www.') {
            $website_host = substr($website_host, 4);
        }

        if (substr($uri_host, 0, 4) == 'www.') {
            $uri_host = substr($uri_host, 4);
        }

        // If on same host, convert to relative
        if (pyncer_ltrim_string($uri_host, $website_host) !== $uri_host) {
            $pos = strpos($uri, $website_host) + strlen($website_host);
            $char = substr($uri, $pos, 1);
            // Make sure end of host is a separator character
            // example.com/ vs example.comm/
            if (!$char || in_array($char, ['/', '?', '#'])) {
                $uri = substr($uri, $pos);

                if (!$uri) {
                    $uri = '/';
                }
            }
        } elseif (!$protocal &&
            !in_array(substr($uri, 0, 1), ['/', '?', '#'])
        ) {
            // Determine if there is a host present
            $pos = pyncer_str_pos_array($uri, ['/', '?', '#']);
            if ($pos !== false) {
                $uri_host = pyncer_str_sub($uri, 0, $pos['index']);
            } else {
                $uri_host = $uri;
            }

            // If there is a dot, then there is a host
            if (str_contains($uri_host, '.')) {
                $uri = 'http://' . $uri;
            } else {
                $uri = '/' . $uri;
            }
        }

        return $uri;
    }

    $offset = strpos($uri, '://');
    if ($offset !== false) {
        $offset += 3;
    } else {
        $offset = 0;
    }

    $pos = pyncer_str_pos_array($uri, ['/', '?', '#'], $offset);

    // Nothing after the host
    if ($pos === false) {
        return '/';
    }

    $uri = pyncer_str_sub($uri, $pos['index']);
    if (!$uri) {
        $uri = '/';
    }

    return $uri;
}
function absolute_uri(string $uri, string $to): string
{
    if (str_contains($uri, '://')) {
        return $uri;
    }

    return rtrim($to, '/') . '/' . ltrim($uri, '/');
}

function uri_equals(string $uri1, string $uri2): bool
{
    // Parse the URLs into their component parts
    $parts1 = parse_url($uri1);
    $parts2 = parse_url($uri2);

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

function encode_uri(string $uri, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $uri = rawurlencode($uri);

    if ($encodeMethod === PHP_QUERY_RFC1738) {
        $uri = str_replace('%20', '+', $uri);
    }

    return $uri;
}

function decode_uri(string $uri, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    if ($encodeMethod === PHP_QUERY_RFC1738) {
        $uri = str_replace('+', ' ', $uri);
    }

    return rawurldecode($uri);
}

function encode_uri_path(string $path, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $result = preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/]+|%(?![A-Fa-f0-9]{2}))/',
        function ($match) use ($encodeMethod) {
            return encode_uri($match[0], $encodeMethod);
        },
        $path
    );

    if ($result === null) {
        throw new RuntimeException('URL path could not be encoded.');
    }

    return $result;
}

function encode_uri_user_info(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $result = preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=]+|%(?![A-Fa-f0-9]{2}))/u',
        function ($match) use ($encodeMethod) {
            return encode_uri($match[0], $encodeMethod);
        },
        $value
    );

    if ($result === null) {
        throw new RuntimeException('URL user info could not be encoded.');
    }

    return $result;
}
function encode_uri_query(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $result = preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
        function ($match) use ($encodeMethod) {
            return encode_uri($match[0], $encodeMethod);
        },
        $value
    );

    if ($result === null) {
        throw new RuntimeException('URL query could not be encoded.');
    }

    return $result;
}
function encode_uri_fragment(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return encode_uri_query($value, $encodeMethod);
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

/**
 * @deprecated Use clean_uri
 */
function clean_url(string $url): string
{
    return clean_uri($url);
}

/**
 * @deprecated parse_uri_query
 * @return array<int|string, mixed> An array of query key value pairs.
 */
function parse_url_query(string $query): array
{
    return parse_uri_query($query);
}

/**
 * @deprecated Use merge_uri_queries.
 * @param string|iterable<int|string, mixed> ...$queries An array of queries to merge.
 * @return array<int|string, mixed> The merged queries.
 */
function merge_url_queries(string|iterable ...$queries): array
{
    return merge_uri_queries(...$queries);
}

/**
 * @deprecated Use build_uri_query.
 * @param iterable<int|string, mixed> $query An iterable object of key value pairs.
 * @param int $encodeMethod The encode method to use to encode values.
 * @return string The built query string.
 */
function build_url_query(iterable $query, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return build_uri_query($query, $encodeMethod);
}

/**
 * @deprecated Use relative_uri.
 */
function relative_url(string $url, ?string $to = null): string
{
    return relative_uri($url, $to);
}

/**
 * @deprecated Use absolute_uri.
 */
function absolute_url(string $url, string $to): string
{
    return absolute_uri($url, $to);
}

/**
 * @deprecated Use uri_equals.
 */
function url_equals(string $url1, string $url2): bool
{
    return uri_equals($url1, $url2);
}

/**
 * @deprecated Use encode_uri.
 */
function encode_url(string $url, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return encode_uri($url, $encodeMethod);
}

/**
 * @deprecated Use decode_uri.
 */
function decode_url(string $url, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return decode_uri($url, $encodeMethod);
}

/**
 * @deprecated Use encode_uri_path.
 */
function encode_url_path(string $path, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return encode_uri_path($path, $encodeMethod);
}

/**
 * @deprecated Use encode_uri_user_info.
 */
function encode_url_user_info(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return encode_uri_user_info($value, $encodeMethod);
}

/**
 * @deprecated Use encode_uri_query.
 */
function encode_url_query(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return encode_uri_query($value, $encodeMethod);
}

/**
 * @deprecated Use encode_uri_fragment.
 */
function encode_url_fragment(string $value, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    return encode_uri_fragment($value, $encodeMethod);
}
