<?php
namespace Pyncer;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

use function date_default_timezone_get;
use function html_entity_decode;
use function htmlspecialchars;
use function is_int;
use function mb_internal_encoding;
use function mb_http_output;
use function mb_regex_encoding;

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
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
function hd(string $string): string
{
    // We want to decode all entities, not just html entities
    return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
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

    if (is_int($date)) {
        if ($date == -1) {
            if (defined('Pyncer\NOW')) {
                $date = '@' . PYNCER_NOW;
            } else {
                $date = '@' . time();
            }
        } else {
            $date = '@' . $date;
        }
    }

    $date = new DateTime(strval($date));

    if (!$local) {
        // Timezone set outside of constructor since
        // @xyz format ignores it otherwise
        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    return $date;
}
