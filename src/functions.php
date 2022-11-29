<?php
namespace Pyncer;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

use function class_exists as php_class_exists;
use function class_parents as php_class_parents;
use function class_uses as php_class_uses;
use function class_implements as php_class_implements;
use function date_default_timezone_get;
use function html_entity_decode;
use function htmlspecialchars;
use function is_int;
use function ltrim;
use function strcasecmp;

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

function class_parents(string $class, string $parent, bool $autoLoad = false): bool
{
    if (php_class_exists($class, $autoLoad)) {
        if ($parents = php_class_parents($class)) {
            foreach ($parents as $value) {
                if (strcasecmp($parent, $value) == 0) {
                    return true;
                }
            }
        }
    }

    return false;
}
function class_uses(string $class, string $trait, bool $autoLoad = false): bool
{
    if (php_class_exists($class, $auto_load)) {
        // We need to check all its parents too
        $parents = php_class_parents($class, $autoLoad);
        $parents[] = $class;

        foreach ($parents as $parent) {
            if ($traits = php_class_uses($parent)) {
                foreach ($traits as $value) {
                    if (strcasecmp($trait, $value) == 0) {
                        return true;
                    }
                }
            }
        }
    }

    return false;
}
function class_implements(string $class, string $interface, bool $autoLoad = false): bool
{
    if (php_class_exists($class, $autoLoad)) {
        if ($interfaces = php_class_implements($class)) {
            $interface = ltrim($interface, '\\');

            foreach ($interfaces as $value) {
                if (strcasecmp($interface, $value) == 0) {
                    return true;
                }
            }
        }
    }

    return false;
}
