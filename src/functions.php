<?php
namespace Pyncer;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Pyncer\Exception\InvalidArgumentException;
use Stringable;

use function com_create_guid;
use function date_default_timezone_get;
use function defined;
use function html_entity_decode;
use function htmlspecialchars;
use function is_array;
use function is_int;
use function is_iterable;
use function is_scalar;
use function is_string;
use function mb_internal_encoding;
use function mb_http_output;
use function mb_regex_encoding;
use function sprintf;
use function strval;
use function time;
use function trim;

use const Pyncer\DATE_TIME_FORMAT as PYNCER_DATE_TIME_FORMAT;
use const Pyncer\ENCODING as PYNCER_ENCODING;
use const Pyncer\NOW as PYNCER_NOW;

function initialize(): void
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'initialize.php';

    mb_internal_encoding(PYNCER_ENCODING);
    mb_http_output(PYNCER_ENCODING);
    mb_regex_encoding(PYNCER_ENCODING);
}

/**
 * Returns the specified default value if $value is an empty value for
 * its type.
 *
 * @param mixed $value The value to nullify.
 * @param mixed $default The value to return if nullifiable.
 * @return mixed The nullified value.
 */
function nullify(mixed $value, mixed $default = null): mixed
{
    if ($value === '' ||
        $value === false ||
        $value === 0 ||
        $value === 0.0 ||
        $value === [] ||
        $value === null
    ) {
        return $default;
    }

    return $value;
}

/**
 * @return null|bool|int|float|string|array<mixed>
 */
function basify(mixed $value): null|bool|int|float|string|array
{
    if (is_array($value)) {
        return $value;
    }

    if (is_iterable($value)) {
        return [...$value];
    }

    return scalarify($value);
}

/**
 * @return null|bool|int|float|string
 */
function scalarify(mixed $value): null|bool|int|float|string
{
    if (is_scalar($value)) {
        return $value;
    }

    if ($value instanceof DateTimeInterface) {
        return $value->format(PYNCER_DATE_TIME_FORMAT);
    }

    if ($value instanceof Stringable) {
        return strval($value);
    }

    return null;
}

/**
 * @return null|string
 */
function stringify(mixed $value): ?string
{
    $value = scalarify($value);

    if ($value !== null) {
        return strval($value);
    }

    return null;
}

function he(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, PYNCER_ENCODING);
}

function hd(string $string): string
{
    // We want to decode all entities, not just html entities
    return html_entity_decode($string, ENT_QUOTES, PYNCER_ENCODING);
}

function date_time(mixed $date = -1, bool $local = false): ?DateTime
{
    if ($date instanceof DateTimeInterface) {
        $date = new DateTime(
            '@' . $date->getTimestamp()
        );

        if (!$local) {
            $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        }

        return $date;
    }

    // null, false, '', 0, 0 filled date
    if (nullify($date) === null) {
        return null;
    }

    if ($date === true) {
        $date = '@' . time();
    } elseif (is_int($date)) {
        if ($date === -1) {
            if (defined('Pyncer\NOW')) {
                $date = '@' . PYNCER_NOW;
            } else {
                $date = '@' . time();
            }
        } else {
            $date = '@' . $date;
        }
    }

    if (!is_string($date) && !$date instanceof Stringable) {
        throw new InvalidArgumentException('Invalid date.');
    }

    $date = new DateTime(strval($date));

    if (!$local) {
        // Timezone set outside of constructor since
        // \@xyz format ignores it otherwise
        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    return $date;
}

function uid(): string
{
    if (function_exists('com_create_guid') === true) {
        $uid = com_create_guid();

        if ($uid !== false) {
            return strtolower(trim($uid, '{}'));
        }
    }

    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(16384, 20479),
        mt_rand(32768, 49151),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535)
    );
}
