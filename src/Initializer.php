<?php
namespace Pyncer;

use Pyncer\Exception\FileNotFoundException;

use const Pyncer\ENCODING as PYNCER_ENCODING;

class Initializer
{
    protected static array $files = [];

    private function __construct()
    {}

    public static function register(string ...$files): void
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new FileNotFoundException($file);
            }

            if (!in_array($file, static::$files)) {
                static::$files[] = $file;
            }
        }
    }

    public static function initialize(): void
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'initialize.php';

        foreach (static::$files as $file) {
            require_once $file;
        }

        mb_internal_encoding(PYNCER_ENCODING);
        mb_http_output(PYNCER_ENCODING);
        mb_regex_encoding(PYNCER_ENCODING);
    }

    public static function define(string $constantName, mixed $value): void
    {
        defined($constantName) or define($constantName, $value);
    }

    public static function defineFrom(string $constantName, string $fromConstantName, mixed $defaultValue): void
    {
        if (!defined($constantName)) {
            if (defined($fromConstantName)) {
                define($constantName, constant($fromConstantName));
            } else {
                define($constantName, $defaultValue);
            }
        }
    }
}
