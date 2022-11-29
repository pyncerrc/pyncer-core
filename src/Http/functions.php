<?php
namespace Pyncer\Http;

use Pyncer\Exception\InvalidArgumentException;

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
use function parse_str;
use function preg_replace_callback;
use function Pyncer\String\len as pyncer_str_len;
use function Pyncer\String\ltrim_string as pyncer_ltrim_string;
use function Pyncer\String\pos as pyncer_str_pos;
use function Pyncer\String\pos_array as pyncer_str_pos_array;
use function Pyncer\String\rtrim_string as pyncer_rtrim_string;
use function Pyncer\String\sub as pyncer_str_sub;
use function rawurlencode;
use function rawurldecode;
use function rtrim;
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
    $parts = explode('://', $url);

    if (count($parts) === 1) {
        throw new InvalidArgumentException('Invalid url. (' . $url . ')');
    }

    $parts[1] = explode('?', $parts[1], 2);

    $parts[1][0] = clean_path($parts[1][0]);

    $parts[1] = implode('?', $parts[1]);

    return implode('://', $parts);
}
function clean_path(string $path): string
{
    $path = strval($path);

    if ($path === '') {
        return '';
    }

    // Ensure URL path separators
    $path = str_replace(['\\', DS], '/', $path);

    // Ensure start slash if no protocol
    if (strpos($path, '://') === false && substr($path, 0, 1) != '/') {
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

function parse_url_query(string $query): array
{
    $q = [];

    $query = ltrim(strval($query), '?');

    if (!$query) {
        return $q;
    }

    foreach (explode('&', $query) as $part) {
        $part = explode('=', $part, 2);
        $count = count($part);

        // TODO: Replace parse_str with non mangling function
        // The parse_str function does not suxp empty values
        // so we set it as one and remove it after
        if ($count == 1) {
            $part[1] = 1;
        }

        $single = [];
        parse_str($part[0] . '=' . $part[1], $single);

        // Change the overriden value back to null
        if ($count == 1) {
            if (is_array($single[1])) {
                array_walk_recursive($single, function(&$value, $key) {
                    $value = null;
                });
            } else {
                $single[1] = null;
            }
        }

        $q = array_merge($q, $single);
    }

    return $q;
}
function merge_url_queries(string|array ...$queries): array
{
    $q = [];

    foreach ($queries as $query) {
        if (!is_array($query)) {
            $query = parse_url_query($query);
        }

        $q = array_merge($q, $query);
    }

    return $q;
}
function build_url_query(array $query, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $queryParts = [];

    foreach ($query as $key => $value) {
        $key = encode_url($key, $encodeMethod);

        if ($value === null) {
            $queryParts[] = $key;
        } elseif (is_array($value)) {
            $queryParts[] = http_build_query([$key => $value], null, '&', $encodeMethod);
        } else {
            $value = encode_url($value, $encodeMethod);
            $queryParts[] = $key . '=' . $value;
        }
    }

    return implode('&', $queryParts);
}

function to_relative_url(string $url, string $to = null): string
{
    $to = strval($to);
    if ($to !== '') {
        // Remove protocal if any
        $pos = pyncer_str_pos($to, '//');
        if ($pos === false) {
            $website_domain = $to;
        } else {
            $website_domain = pyncer_str_sub($to, $pos + 2);
        }
        $website_domain = rtrim($website_domain, '/');

        // Remove protocal if any
        $pos = pyncer_str_pos($url, '//');
        if ($pos === false) {
            $url_domain = $url;
        } else {
            $url_domain = pyncer_str_sub($url, $pos + 2);
        }
        $protocal = ($url_domain != $url);

        // Remove www. before comparing
        if (pyncer_str_sub($website_domain, 0, 4) == 'www.') {
            $website_domain = pyncer_str_sub($website_domain, 4);
        }
        if (pyncer_str_sub($url_domain, 0, 4) == 'www.') {
            $url_domain = pyncer_str_sub($url_domain, 4);
        }

        // If on same host, convert to relative
        if (pyncer_ltrim_string($url_domain, $website_domain) !== false) {
            $pos = pyncer_str_pos($url, $website_domain) + pyncer_str_len($website_domain);
            $char = pyncer_str_sub($url, $pos, 1);
            // Make sure end of domain is a separator character
            // example.com/ vs example.comm/
            if (!$char || in_array($char, ['/', '?', '#'])) {
                $url = pyncer_str_sub($url, $pos);

                if (!$url) {
                    $url = '/';
                }
            }
        } elseif (!$protocal && !in_array(pyncer_str_sub($url, 0, 1), ['/', '?', '#'])) {
            // Determine if there is a domain present
            $pos = pyncer_str_pos_array($url, ['/', '?', '#']);
            if ($pos !== false) {
                $url_domain = pyncer_str_sub($url, 0, $pos['index']);
            } else {
                $url_domain = $url;
            }

            // If there is a dot, then there is a domain
            if (pyncer_str_pos($url_domain, '.') !== false) {
                $url = 'http://' . $url;
            } else {
                $url = '/' . $url;
            }
        }

        return $url;
    }

    $pos = pyncer_str_pos($url, '://');
    if ($pos !== false) {
        $url = pyncer_str_sub($url, $pos + 3);
    }

    $pos = pyncer_str_pos_array($url, ['/', '?', '#']);

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
function to_absolute_url(string $url, string $to): string
{
    if (pyncer_str_sub($url, 0, 1) == '/') {
        return rtrim($to, '/') . $url;
    }

    return $url;
}

function encode_url(string $url, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    $url = rawurlencode($url);

    if ($encodeMethod == PHP_QUERY_RFC1738) {
        $url = str_replace('%20', '+', $url);
    }

    return $url;
}
function decode_url(string $url, int $encodeMethod = PHP_QUERY_RFC3986): string
{
    if ($encodeMethod == PHP_QUERY_RFC1738) {
        $url = str_replace('+', ' ', $url);
    }

    return rawurldecode($url);
}

function encode_url_path(string $path, int $encodeMethod = PHP_QUERY_RFC3986): ?string
{
    return preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/]+|%(?![A-Fa-f0-9]{2}))/',
        function ($match) {
            return encode_url($match[0], $encodeMethod);
        },
        $path
    );
}

function encode_url_user_info(string $value, int $encodeMethod = PHP_QUERY_RFC3986): ?string
{
    return preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=]+|%(?![A-Fa-f0-9]{2}))/u',
        function ($match) use ($encodeMethod) {
            return encode_url($match[0], $encodeMethod);
        },
        $value
    );
}
function encode_url_query(string $value, int $encodeMethod = PHP_QUERY_RFC3986): ?string
{
    return preg_replace_callback(
        '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
        function ($match) use ($encodeMethod) {
            return encode_url($match[0], $encodeMethod);
        },
        $value
    );
}
function encode_url_fragment(string $value, int $encodeMethod = PHP_QUERY_RFC3986): ?string
{
    return encode_url_query($value, $encodeMethod);
}

function base64_encode(string $data): ?string
{
    return rtrim(strtr(php_base64_encode($data), '+/', '-_'), '=');
}
function base64_decode(string $data): ?string
{
    return php_base64_decode(str_pad(
        strtr($data, '-_', '+/'),
        strlen($data) % 4,
        '=',
        STR_PAD_RIGHT
    ));
}
