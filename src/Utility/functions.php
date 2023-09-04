<?php
namespace Pyncer\Utility;

use function class_exists as php_class_exists;
use function class_parents as php_class_parents;
use function class_uses as php_class_uses;
use function class_implements as php_class_implements;
use function array_map;
use function implode;
use function lcfirst;
use function ltrim;
use function preg_match_all;
use function strcasecmp;

function to_pascal_case(string $string): string
{
    return implode('', array_map('ucfirst', split_case($string)));
}

function to_camel_case(string $string): string
{
     return lcfirst(to_pascal_case($string));
}

function to_snake_case(string $string): string
{
    return implode('_', array_map('strtolower', split_case($string)));
}

function to_kebab_case(string $string): string
{
    return implode('-', array_map('strtolower', split_case($string)));
}

/**
 * Splits a name at capitalized words or underscores.
 *
 * @param string $string The string to split.
 * @return array<string> The split string.
 */
function split_case(string $string): array
{
    $result = preg_split(
        '/((?<=[a-z])(?=[A-Z])|(?=[A-Z][a-z])|_|-)/',
        $string,
        0,
        PREG_SPLIT_NO_EMPTY
    );

    if ($result === false) {
        return [];
    }

    return $result;
}

function class_parents(
    string $class,
    string $parent,
    bool $autoLoad = false
): bool
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

function class_uses(
    string $class,
    string $trait,
    bool $autoLoad = false
): bool
{
    if (php_class_exists($class, $autoLoad)) {
        // We need to check all its parents too
        $parents = php_class_parents($class, $autoLoad);

        if ($parents === false) {
            return false;
        }

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

function class_implements(
    string $class,
    string $interface,
    bool $autoLoad = false
): bool
{
    if (!php_class_exists($class, $autoLoad)) {
        return false;
    }

    $interfaces = php_class_implements($class);
    if (!$interfaces) {
        return false;
    }

    $interface = ltrim($interface, '\\');

    foreach ($interfaces as $value) {
        if (strcasecmp($interface, $value) == 0) {
            return true;
        }
    }

    return false;
}
