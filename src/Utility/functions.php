<?php
namespace Pyncer\Utility;

use function array_map;
use function implode;
use function lcfirst;
use function preg_match_all;

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
