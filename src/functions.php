<?php
namespace Pyncer;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

use function date_default_timezone_get;
use function html_entity_decode;
use function htmlspecialchars;
use function is_int;

use const Pyncer\NOW as PYNCER_NOW;

function initialize()
{
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'initialize.php';
}

function nullify($value, $default = null)
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

function he(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function hd(string $s): string
{
    // We want to decode all entities, not just html entities
    return html_entity_decode($s, ENT_QUOTES, 'UTF-8');
}

function date_time(mixed $date = -1, $local = false): ?DateTime
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
            $date = '@' . PYNCER_NOW;
        } else {
            $date = '@' . $date;
        }
    }

    $date = new DateTime($date);

    if (!$local) {
        // Timezone set outside of constructor since
        // @xyz format ignores it otherwise
        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    return $date;
}
