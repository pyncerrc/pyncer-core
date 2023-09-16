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
use function is_int;
use function is_string;
use function mb_internal_encoding;
use function mb_http_output;
use function mb_regex_encoding;
use function sprintf;
use function time;
use function trim;

use const Pyncer\NOW as PYNCER_NOW;
use const Pyncer\ENCODING as PYNCER_ENCODING;

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
