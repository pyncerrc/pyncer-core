<?php
namespace Pyncer\Utility;

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

function split_case(string $string): array
{
    preg_match_all(
        '/[A-Z0-9][^A-Z0-9_]*+|(?:\A|(?<=_))[^A-Z0-9_]++|\A\z/',
        $string,
        $matches
    );

    return $matches[0];
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
