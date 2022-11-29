<?php
namespace Pyncer;

use function date;
use function define;
use function defined;
use function getcwd;
use function time;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

defined('Pyncer\NL') or define('Pyncer\NL', "\n");

defined('Pyncer\NOW') or define('Pyncer\NOW', time());
defined('Pyncer\DATE_FORMAT') or define('Pyncer\DATE_FORMAT', 'Y-m-d');
defined('Pyncer\TIME_FORMAT') or define('Pyncer\TIME_FORMAT', 'H:i:s');
defined('Pyncer\DATE_TIME_FORMAT') or define('Pyncer\DATE_TIME_FORMAT', DATE_FORMAT . ' ' . TIME_FORMAT);

defined('Pyncer\DATE_NOW') or define('Pyncer\DATE_NOW', date(DATE_FORMAT, NOW));
defined('Pyncer\TIME_NOW') or define('Pyncer\TIME_NOW', date(TIME_FORMAT, NOW));
defined('Pyncer\DATE_TIME_NOW') or define('Pyncer\DATE_TIME_NOW', date(DATE_TIME_FORMAT, NOW));

defined('Pyncer\IO\DIR_ROOT') or define('Pyncer\IO\DIR_ROOT', getcwd());
defined('Pyncer\IO\MODE_FILE') or define('Pyncer\IO\MODE_FILE', 0644);
defined('Pyncer\IO\MODE_DIR') or define('Pyncer\IO\MODE_DIR', 0755);

defined('Pyncer\IO\BAD_PATH_CHARS') or define('Pyncer\IO\BAD_PATH_CHARS', '/\\?*:|"<>');
defined('Pyncer\IO\BAD_PATHS') or define('Pyncer\IO\BAD_PATHS', [
    '$Extend'
]);
defined('Pyncer\IO\BAD_FILENAME_CHARS') or define('Pyncer\IO\BAD_FILENAME_CHARS', '/\\?*:|"<>');
defined('Pyncer\IO\BAD_FILENAMES') or define('Pyncer\IO\BAD_FILENAMES', [
    'CON', 'PRN', 'AUX', 'CLOCK$', 'NUL',
    'COM0', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
    'LPT0', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9',
    '$Mft', '$MftMirr', '$LogFile', '$Volume', '$AttrDef', '$Bitmap', '$Boot', '$BadClus', '$Secure',
    '$Upcase', '$Extend', '$Quota', '$ObjId', '$Reparse'
]);
