<?php
namespace Pyncer\IO;

use Pyncer\Exception\RuntimeException;
use Pyncer\Exception\InvalidArgumentException;

use function chmod as php_chmod;
use function clearstatcache;
use function closedir;
use function copy as php_copy;
use function dirname;
use function explode;
use function file_exists;
use function file_put_contents;
use function filetype;
use function feof;
use function fileperms;
use function floor;
use function fopen;
use function fread;
use function fseek;
use function implode;
use function in_array;
use function ini_set;
use function is_dir;
use function is_link;
use function is_readable;
use function is_writeable;
use function is_string;
use function log;
use function ltrim;
use function max;
use function mb_strlen;
use function min;
use function mkdir;
use function move_uploaded_file as php_move_uploaded_file;
use function mt_rand;
use function opendir;
use function pathinfo;
use function pow;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function Pyncer\Array\ensure_array as pyncer_ensure_array;
use function Pyncer\Array\unset_empty as pyncer_array_unset_empty;
use function Pyncer\String\len as pyncer_str_len;
use function Pyncer\String\rpos as pyncer_str_rpos;
use function Pyncer\String\sub as pyncer_str_sub;
use function Pyncer\String\to_lower as pyncer_str_to_lower;
use function readdir;
use function rename as php_rename;
use function rmdir;
use function round;
use function rtrim;
use function str_replace;
use function stream_get_meta_data;
use function strrpos;
use function strtolower;
use function substr;
use function trim;
use function umask;
use function unlink;
use function uniqid;

use const DIRECTORY_SEPARATOR as DS;
use const Pyncer\IO\DIR_MODE as PYNCER_IO_DIR_MODE;
use const Pyncer\IO\FILE_MODE as PYNCER_IO_FILE_MODE;
use const Pyncer\IO\ROOT_DIR as PYNCER_IO_ROOT_DIR;
use const Pyncer\IO\BAD_PATH_CHARACTERS as PYNCER_IO_BAD_PATH_CHARACTERS;
use const Pyncer\IO\BAD_PATHS as PYNCER_IO_BAD_PATHS;
use const Pyncer\IO\BAD_FILENAME_CHARACTERS as PYNCER_IO_BAD_FILENAME_CHARACTERS;
use const Pyncer\IO\BAD_FILENAMES as PYNCER_IO_BAD_FILENAMES;

/**
 * Gets an array of files contained within the specified directory.
 *
 * Optionally you can specify one or more extensions to limit results to.
 *
 * Each file entry in the array is a pathinfo array.
 *
 * @param string $dir The directory to search in.
 * @param null|string|array<string> $extensions Optional extensions to limit results to.
 * @return array<array{'dirname': string, 'basename': string, 'extension': null|string, 'filename': string}> An array of files found.
 * @throws \Pyncer\Exception\InvalidArgumentException When the specified
 *      directory is invalid.
 * @throws \Pyncer\Exception\RuntimeException When the specified directory
 *      could not be read.
 */
function files(string $dir, null|string|array $extensions = null): array
{
    $dir = clean_dir($dir);
    $extensions = pyncer_ensure_array($extensions, [null, '']);

    if (!is_dir($dir)) {
        throw new InvalidArgumentException('Invalid directory.');
    }

    $handle = opendir($dir);

    if (!$handle) {
        throw new RuntimeException('Directory could not be opened.');
    }

    $files = [];

    while (($path = readdir($handle)) !== false) {
        // Skip the reference to current and parent directory
        if ($path === '.' || $path === '..') {
            continue;
        }

        if (is_dir($dir . DS . $path)) {
            continue;
        }

        $info = pathinfo($dir . DS . $path);

        $info['extension'] ??= null;

        if ($extensions &&
            $info['extension'] !== '' &&
            $info['extension'] !== null &&
            !in_array(strtolower($info['extension']), $extensions)
        ) {
            continue;
        }

        $files[] = $info;
    }

    closedir($handle);

    usort($files, function($a, $b) {
        return ($a['basename'] <=> $b['basename']);
    });

    return $files;
}

/**
 * Gets an array of file names contained within the specified directory.
 *
 * @param string $dir The directory to search in.
 * @param null|string|array<string> $extensions Optional extensions to limit results to.
 * @param bool $removeExtension Whether the file extension should be removed
 *      from the filenames returned.
 * @return array<string> An array of files found.
 */
function filenames(
    string $dir,
    null|string|array $extensions = null,
    bool $removeExtension = false
): array
{
    $files = [];

    $filesInfo = files($dir, $extensions);

    if ($removeExtension) {
        foreach ($filesInfo as $value) {
            $files[] = $value['filename'];
        }
    } else {
        foreach ($filesInfo as $value) {
            $files[] = $value['basename'];
        }
    }

    return $files;
}

function filename(string $file, bool $removeExtension = false): ?string
{
    $pos = strrpos($file, DS);
    if ($pos !== false) {
        $file = substr($file, $pos + strlen(DS));
    }

    if ($removeExtension) {
        $extension = extension($file);

        if ($extension !== null) {
            $file = substr($file, 0, -(strlen($extension) + 1));
        }
    }

    return ($file !== '' ? $file : null);
}

function extension(string $file): ?string
{
    $pos = strrpos($file, '.');

    if ($pos !== false) {
        $extension = substr($file, $pos + 1);

        if (DS !== '/') {
            $extension = str_replace('/', DS, $extension);
        }

        // If there is a path separator, than there is no extension
        if (str_contains($extension, DS)) {
            return null;
        }

        return ($extension !== '' ? $extension : null);
    }

    return null;
}

function replace_extension(string $file, ?string $extension = null): string
{
    $currentExtension = extension($file);
    if ($currentExtension !== null) {
        $file = substr($file, 0, -(strlen($currentExtension) + 1));
    }

    if ($extension !== null) {
        $file .= '.' . ltrim($extension, '.');
    }

    return $file;
}

function filesize_from_string(string $string): int
{
    return mb_strlen($string, '8bit');
}

function write_file(
    string $file,
    string $data,
    bool $append = false,
    ?int $mode = PYNCER_IO_FILE_MODE
): int
{
    $dir = dirname($file);
    if (!file_exists($dir)) {
        make_dir($dir);
    }

    $result = file_put_contents($file, $data, ($append ? FILE_APPEND : 0));

    if ($result === false) {
        throw new RuntimeException('File is not writeable.');
    }

    if ($mode !== null) {
        @php_chmod($file, $mode);
    }

    return $result;
}

function read_file(string $file, int $length = 0, int $offset = 0): string
{
    if (!file_exists($file)) {
        throw new RuntimeException('File not found.');
    }

    if (!is_readable($file)) {
        throw new RuntimeException('File is not readable.');
    }

    if ($length < 0) {
        throw new InvalidArgumentException(
            'Length must be greater than or equal to zero.'
        );
    }

    $handle = fopen($file, 'rb');

    if (!$handle) {
        throw new RuntimeException('File is not readable.');
    }

    clearstatcache(false, $file);

    if ($offset) {
        fseek($handle, $offset);
    }

    $data = '';
    $amount = 0;

    if ($length === 0) {
        $readLength = 4096;
    } else {
        $readLength = min($length, 4096);
    }

    while (!feof($handle) && (!$length || $amount < $length)) {
        $data .= fread($handle, $readLength);
        $amount += 4096;
    }

    fclose($handle);

    return $data;
}

function download_file(
    string $fileUrl,
    string $dir,
    string $filename = '',
    ?int $mode = PYNCER_IO_FILE_MODE
): string
{
    // Set pyncer as the user agent
    ini_set('user_agent', 'Pyncer');

    $handle = fopen($fileUrl, 'rb');
    if (!$handle) {
        throw new RuntimeException('Source file is not readable.');
    }

    if ($filename === '') {
        // Check header for filename if not specified
        $metaData = stream_get_meta_data($handle);
        if (array_key_exists('wrapper_data', $metaData) &&
            is_iterable($metaData['wrapper_data'])
        ) {
            foreach ($metaData['wrapper_data'] as $wrapper_data) {
                $wrapper_data = strval($wrapper_data);

                if (substr($wrapper_data, 0, 19) == 'Content-Disposition') {
                    $filename = explode ('"', $wrapper_data);
                    $filename = $filename[1];
                    break;
                }
            }
        }
    }

    // Set filename if not specified
    if ($filename === '') {
        $parts = explode('://', trim($fileUrl, '/'), 2);
        if (count($parts) === 2) {
            $parts = $parts[1];

            $parts = explode('/', $parts);

            // We don't want to use domain as filename so skip over it
            if (count($parts) > 1) {
                $filename = $parts[count($parts) - 1];
            }
        } else {
            $parts = $parts[0];

            $parts = explode('/', $parts);

            $filename = $parts[count($parts) - 1];
        }
    }

    // If still no filename then file url was is a directory
    if ($filename === '') {
        fclose($handle);
        throw new RuntimeException('Source filename could not be determined.');
    }

    $data = '';
    while (!feof($handle)) {
        $dataPart = fread($handle, 4096);

        if ($dataPart === false) {
            fclose($handle);
            throw new RuntimeException('Source file is not readable.');
        }

        $data .= $dataPart;
    }

    fclose($handle);

    $dir = clean_dir($dir);
    $file = $dir . DS . $filename;

    write_file($file, $data, false, $mode);

    return $file;
}

function move_uploaded_file(
    string $from,
    string $to,
    ?int $mode = PYNCER_IO_FILE_MODE
): void
{
    $dir = dirname($from);

    if (!is_dir($dir)) {
        make_dir($dir);
    }

    if (!php_move_uploaded_file($from, $to)) {
        throw new RuntimeException('Uploaded file could not be moved.');
    }

    if ($mode !== null) {
        @php_chmod($to, $mode);
    }
}

/**
 * Gets an array of directories contained within the specified directory.
 *
 * Each file entry in the array is a pathinfo array.
 *
 * @param string $dir The directory to search in.
 * @return array<array{'dirname': string, 'basename': string}> The directories found.
 * @throws \Pyncer\Exception\InvalidArgumentException When the specified
 *      directory is invalid.
 * @throws \Pyncer\Exception\RuntimeException When the specified directory
 *      could not be read.
 */
function dirs(string $dir): array
{
    $dir = clean_dir($dir);

    if (!is_dir($dir)) {
        throw new InvalidArgumentException('Invalid directory.');
    }

    $handle = opendir($dir);

    if (!$handle) {
        throw new RuntimeException('Directory could not be opened.');
    }

    $dirs = [];

    while (($path = readdir($handle)) !== false) {
        if ($path === '.' || $path === '..') {
            continue;
        }

        if (!is_dir($dir . DS . $path)) {
            continue;
        }

        $info =  pathinfo($dir . DS . $path);
        unset($info['extension'], $info['filename']);

        $dirs[] = $info;
    }

    closedir($handle);

    usort($dirs, function($a, $b) {
        return ($a['basename'] <=> $b['basename']);
    });

    return $dirs;
}

/**
 * Gets an array of directory names contained within the specified directory.
 *
 * @param string $dir The directory to search in.
 * @return array<string> An array of directory names found.
 */
function dirnames(string $dir): array
{
    $dirnames = [];

    $dirs = dirs($dir);

    foreach ($dirs as $value) {
        $dirnames[] = $value['basename'];
    }

    return $dirnames;
}

/**
 * Determines whether or not a directory is empty.
 *
 * @param string $dir The directroy to check.
 * @param array<string> $ignore An array of direcories and files to ignore.
 * @return bool True if the directory is empty, otherwise false.
 */
function is_empty(string $dir, array $ignore = []): bool
{
    $dir = clean_dir($dir);

    if (!is_dir($dir)) {
        throw new InvalidArgumentException('Invalid directory.');
    }

    $handle = opendir($dir);

    if (!$handle) {
        throw new RuntimeException('Directory could not be opened.');
    }

    $isEmpty = true;

    while (($path = readdir($handle)) !== false) {
        if ($path === '.' || $path === '..') {
            continue;
        }

        if (in_array($path, $ignore)) {
            continue;
        }

        $isEmpty = false;
        break;
    }

    closedir($handle);

    return $isEmpty;
}

function copy(string $from, string $to, bool $overwrite = false): void
{
    if (!file_exists($from)) {
        throw new RuntimeException('Source not found.');
    }

    // If the src is a directory then copy all contents to
    // destination which is also a directory
    if (is_dir($from) && !is_link($from)) {
        $from = clean_dir($from);
        $to = clean_dir($to);

        if (!file_exists($to)) {
            make_dir($to);
        } elseif (!is_dir($to)) {
            throw new RuntimeException('Destination is not a directory.');
        }

        $handle = opendir($from);

        if ($handle === false) {
            throw new RuntimeException('Directory could not be opened.');
        }

        while (($path = readdir($handle)) !== false) {
            if ($path === '.' || $path === '..') {
                continue;
            }

            switch (filetype($from . DS . $path)) {
                case 'dir':
                case 'file':
                case 'link':
                    copy(
                        $from . DS . $path,
                        $to . DS . $path,
                        $overwrite
                    );
            }
        }

        closedir($handle);

        return;
    }

    // If the src is a file, then copy its contents to
    // destination which is also a file
    $to = clean_dir($to);
    if (is_dir($to) && !is_link($to)) {
        throw new RuntimeException('Destination is not a directory.');
    }

    $parent = dirname($to);
    if (!is_dir($parent)) {
        make_dir($parent);
    }

    if (!$overwrite && file_exists($to)) {
        throw new RuntimeException('Destination file already exists.');
    }

    if (!php_copy($from, $to)) {
        throw new RuntimeException('Source could not be copied.');
    }
}

function move(string $from, string $to, bool $overwrite = false): void
{
    $from = clean_dir($from);

    if (!file_exists($from)) {
        throw new RuntimeException('Source does not exist.');
    }

    // If the src is a directory then copy all contents to
    // destination which is also a directory
    if (is_dir($from) && !is_link($from)) {
        $from = rtrim($from, DS);
        $to = clean_dir($to);

        if (!file_exists($to)) {
            make_dir($to);
        } elseif (!is_dir($to)) {
            throw new RuntimeException('Destination is not a directory.');
        }

        $handle = opendir($from);

        if ($handle === false) {
            throw new RuntimeException('Directory could not be opened.');
        }

        while (($path = readdir($handle)) !== false) {
            if ($path === '.' || $path === '..') {
                continue;
            }

            switch (filetype($from . DS . $path)) {
                case 'dir':
                case 'file':
                case 'link':
                    move(
                        $from . DS . $path,
                        $to . DS . $path,
                        $overwrite
                    );
            }
        }
        closedir($handle);

        // Delete src directory if it is empty
        if (is_empty($from)) {
            delete($from);
        }

        return;
    }

    rename($from, $to, $overwrite);
}

/**
 * Renames a file or directory, if overwrite is true, and the
 * destination file or directory already exists, it will be deleted
 * before renaming the source.
 *
 * @param string $from The file or directory to rename
 * @param string $to The destination file or directory
 * @param bool $overwrite Set to true to overwrite the destinatoin directory
 * @throws \Pyncer\Exception\RuntimeException When a problem occurs.
 */
function rename(
    string $from,
    string $to,
    bool $overwrite = false
): void
{
    if (!file_exists($from)) {
        throw new RuntimeException('Source does not exist.');
    }

    if (file_exists($to)) {
        if (!$overwrite) {
            throw new RuntimeException('Destination already exists.');
        }

        // Only overwrite the same types
        if (is_dir($from) && !is_link($from)) {
            if (!is_dir($to)) {
                throw new RuntimeException('Destination is not a directory.');
            }
        } elseif (is_dir($to) && !is_link($to)) {
            throw new RuntimeException('Destination is not a file.');
        }

        delete($to);
    } else {
        // Ensure the parent rename directory exists
        $dir = dirname($to);
        make_dir($dir);
    }

    if (!php_rename($from, $to)) {
        throw new RuntimeException('Source could not be renamed.');
    }
}

function delete_contents(string $dir): void
{
    $dir = clean_dir($dir);

    if (is_dir($dir) && $handle = opendir($dir)) {
        while (($path = readdir($handle)) !== false) {
            if ($path != '.' && $path != '..') {
               delete($dir . DS . $path);
            }
        }
        closedir($handle);
    }
}
function delete(string $file): void
{
    $file = clean_dir($file);

    if (!file_exists($file)) {
        throw new RuntimeException('File not found.');
    }

    if (!is_dir($file) || is_link($file)) {
        if (!unlink($file)) { // Delete the file
            throw new RuntimeException('File could not be deleted.');
        }

        return;
    }

    $handle = opendir($file);

    if (!$handle) {
        throw new RuntimeException('Directory could not be opened.');
    }

    while (($path = readdir($handle)) !== false) {
        if ($path === '.' || $path === '..') {
            continue;
        }

        delete($file . DS . $path);
    }

    closedir($handle);

    if (!rmdir($file)) {
        throw new RuntimeException('Directory could not be deleted.');
    }
}

/**
 * Deletes all files and directories in $deleteDir that match the
 * files and directories in the $matchDir directory.
 *
 * This function only matches against direct children.
 *
 * @param string $deleteDir Dir to delete files in
 * @param string $matchDir Dir to match against its structure
 * @throws \Pyncer\Exception\RuntimeException When a problem occurs.
 */
function delete_matching(string $deleteDir, string $matchDir): void
{
    $deleteDir = clean_dir($deleteDir);
    if (!file_exists($deleteDir)) {
        throw new RuntimeException('Delete directory does not exist.');
    }

    $matchDir = clean_dir($matchDir);
    if (!file_exists($matchDir)) {
        throw new RuntimeException('Match directory does not exist.');
    }

    if (!is_dir($deleteDir) || is_link($deleteDir)) {
        throw new RuntimeException('Delete directory is not a directory.');
    }

    if (!is_dir($matchDir) || is_link($matchDir)) {
        throw new RuntimeException('Match directory is not a directory.');
    }

    $handle = opendir($matchDir);

    if (!$handle) {
        throw new RuntimeException('Match directory could not be read.');
    }

    while (($path = readdir($handle)) !== false) {
        if ($path === '.' || $path === '..') {
            continue;
        }

        if (file_exists($deleteDir . DS . $path)) {
            delete($deleteDir . DS . $path);
        }
    }
    closedir($handle);
}

/**
 * Makes a new directory.
 *
 * Any parent directories to the specified directory that need to be created
 * will also be created.
 *
 * @param string $dir The directory to make.
 * @param null|int $mode The mode to set on any new directories created.
 * @throws \Pyncer\Exception\RuntimeException If the directory could not
 *      be created.
 */
function make_dir(string $dir, ?int $mode = PYNCER_IO_DIR_MODE): void
{
    $dir = clean_dir($dir);

    if (!defined('Pyncer\IO\ROOT_DIR')) {
        throw new RuntimeException('Pyncer core was not initialized.');
    }

    $dirBase = clean_dir(PYNCER_IO_ROOT_DIR);

    if ($dirBase !== '') { // Clean if base directory is set.
        if ($dir === $dirBase) {
            return;
        }

        $lenDir = strlen($dirBase . DS);
        if (substr($dir, 0, $lenDir) === $dirBase . DS) {
            $dir = DS . substr($dir, $lenDir);
        } else {
            $dirBase = '';
        }
    }

    $paths = explode(DS, $dir);

    // If no base dir, and an empty path[0] then
    // its a linux style path starting with /
    if ($dirBase === '') {
        $dirBase = $paths[0];
        unset($paths[0]);
    }

    // Prevent current umask from affecting mkdir permissions
    $origionalMask = umask(0);

    foreach ($paths as $path) {
        if ($path === '') {
            continue;
        }

        $dirBase .= DS . $path;
        if (!is_dir($dirBase) && !mkdir($dirBase)) {
            umask($origionalMask);
            throw new RuntimeException('Directory could not be made.');
        }

        if ($mode !== null) {
            @php_chmod($dirBase, $mode);
        }
    }

    umask($origionalMask);
}

/**
 * Determines whether or not a file can have its permissions changed.
 *
 * @param string $file The file to check.
 * @return bool True if the file can have its permissions changed,
 *      otherwise false.
 */
function can_chmod(string $file): bool
{
    $permissions = fileperms($file);

    if ($permissions !== false && @php_chmod($file, $permissions ^ 0001)) {
        @php_chmod($file, $permissions);
        return true;
    }

    return false;
}

function chmod(
    string $file,
    ?int $fileMode = PYNCER_IO_FILE_MODE,
    ?int $dirMode = PYNCER_IO_DIR_MODE
): void
{
    if (is_link($file)) {
        throw new RuntimeException('Symbolic link could not have its mode changed.');
    }

    $dir = clean_dir($file);

    if (is_dir($dir)) {
        $handle = opendir($dir);

        if (!$handle) {
            throw new RuntimeException('Directory could not be opened.');
        }

        while (($path = readdir($handle)) !== false) {
            if ($path === '.' || $path === '..') {
                continue;
            }

            $file = $dir . DS . $path;

            // We do not want to change the permissions of linked paths
            if (is_link($file)) {
                continue;
            }

            if (is_dir($file)) {
                chmod($file, $fileMode, $dirMode);
                continue;
            }

            if ($fileMode !== null && !@php_chmod($file, $fileMode)) {
                throw new RuntimeException('File could not have its mode changed.');
            }
        }

        closedir($handle);

        if ($dirMode !== null && !@php_chmod($dir, $dirMode)) {
            throw new RuntimeException('File could not have its mode changed.');
        }
    }

    // $path is a file
    if ($fileMode !== null && !@php_chmod($file, $fileMode)) {
        throw new RuntimeException('File could not have its mode changed.');
    }
}

function clean_dir(string $dir): string
{
    if (DS !== '/') {
        $dir = str_replace('/', DS, $dir);
    }

    // Explode twice so starting / and C:\ will be cut
    $parts = explode(DS, $dir, 2);

    // TODO: Take OS into account
    if ($parts[0] !== '' && !preg_match('/^[a-zA-Z]:$/', $parts[0])) {
        throw new InvalidArgumentException('Invalid directory.');
    }

    $parts[1] = clean_path($parts[1]);

    if ($parts[0] === '') {
        return $parts[1];
    }

    $parts[1] = ltrim($parts[1], DS);

    return implode(DS, $parts);
}

/**
 * Replaces characters and paths that are not supported or reserved by popular
 * operating with an underscore.
 *
 * This function will also replace bad characters and paths with an underscore.
 *
 * @param string $path The path to clean
 * @return string The cleaned path.
 */
function clean_path(string $path): string
{
    // Replace common slashes with directory separator
    if (DS !== '/') {
        $path = str_replace('/', DS, $path);
    }

    $parts = explode(DS, $path);
    $parts = pyncer_array_unset_empty($parts);

    if (!$parts) {
        return '';
    }

    // Remove invalid path characters
    foreach ($parts as $key => $value) {
        // Remove control characters
        $value = preg_replace('/[^[:print:]]/', '_', $value);
        $value = strval($value);

        $value = preg_replace(
            '/[' . preg_quote(PYNCER_IO_BAD_PATH_CHARACTERS, '/') . ']/',
            '_',
            $value
        );
        $value = strval($value);

        $parts[$key]  = $value;

        if ($parts[$key] === '') {
            $parts[$key] = '_';
        } else {
            foreach (PYNCER_IO_BAD_PATHS as $badPath) {
                if (pyncer_str_to_lower($badPath) === pyncer_str_to_lower($value)) {
                    $parts[$key] = '_';
                    break;
                }
            }
        }
    }

    return DS . implode(DS, $parts);
}

function is_valid_path(string $path): bool
{
    // Replace common slashes with directory separator
    if (DS !== '/') {
        $path = str_replace('/', DS, $path);
    }

    $parts = explode(DS, $path);
    $parts = pyncer_array_unset_empty($parts);

    if ($parts) {
        $path = DS . implode(DS, $parts);
    } else {
        $path = '';
    }

    return ($path === clean_path($path));
}

function join_paths(string ...$paths): string
{
    $paths = pyncer_array_unset_empty($paths);

    $joinedPaths = '';

    foreach ($paths as $path) {
        if (DS !== '/') {
            $path = str_replace('/', DS, $path);
        }

        $parts = explode(DS, $path);
        $parts = pyncer_array_unset_empty($parts);

        if ($parts) {
            $path = DS . implode(DS, $parts);
        } else {
            $path = '';
        }

        $joinedPaths .= $path;
    }

    $parts = explode(DS, $joinedPaths);
    $joins = [];
    $index = -1;

    foreach ($parts as $value) {
        // Resolve path
        if ($value === '..' && $index >= 0 && $joins[$index] !== '..') {
            unset($joins[$index]);
            --$index;
            continue;
        }

        ++$index;
        $joins[$index] = $value;
    }

    return implode(DS, $joins);
}

/**
 * Replaces characters and filenames that are not supported or reserved by
 * popular operating with an underscore.
 */
function clean_filename(string $filename): string
{
    // Remove bad characters outright
    $filename = preg_replace('/[^[:print:]]/', '', $filename);
    $filename = strval($filename);

    $filename = preg_replace(
        '/[' . preg_quote(PYNCER_IO_BAD_FILENAME_CHARACTERS, '/') . ']/',
        '_',
        $filename
    );
    $filename = strval($filename);

    $pos = pyncer_str_rpos($filename, '.');
    if ($pos !== false) {
        $extension = pyncer_str_sub($filename, $pos + 1);
        $name = pyncer_str_sub($filename, 0, $pos);
    } else {
        $extension = '';
        $name = $filename;
    }

    // Check if the filename is bad
    foreach (PYNCER_IO_BAD_FILENAMES as $badFilename) {
        if (pyncer_str_to_lower($badFilename) === pyncer_str_to_lower($name)) {
            $name = '_';
            break;
        }
    }

    // Join and remove any possible trailing period
    $filename = rtrim($name . '.' . $extension, '.');

    if ($filename === '') {
        $filename = '_';
    }

    return $filename;
}

function format_filesize(
    int $size,
    int $precision = 0,
    bool $binary = false,
    int $unitOffset = 0
): string
{
    if ($binary) {
        $multiplyer = 1024;
        $sizes = [
            ' Bytes', ' KiB', ' MiB', ' GiB', ' TiB', ' PiB', ' EiB', ' ZiB', ' YiB'
        ];
    } else {
        $multiplyer = 1000;
        $sizes = [
            ' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'
        ];
    }

    // Skip logic for bytes
    if ($size >= $multiplyer) {
        $i = floor(log($size, $multiplyer));
        $size = round($size / pow($multiplyer, $i), $precision);

        return $size . $sizes[$i + $unitOffset];
    }

    return max(0, $size) . $sizes[$unitOffset];
}

/**
 * Checks whether a file or directory is writable.
 *
 * @param string $path The file or directory to check.
 * @return bool True if the path is writable, otherwise false.
 */
function is_writable(string $path): bool
{
    if (is_dir($path)) {
        $path = rtrim($path, DS);
        $filename = uniqid(strval(mt_rand(1000))) . '.tmp';
        return is_writable($path . DS . $filename);
    }

    // Check temporary file for read/write capabilities
    $remove = !file_exists($path);

    $handle = fopen($path, 'a');

    if ($handle === false) {
        return false;
    }

    fclose($handle);

    // Delete file if created for test
    if ($remove) {
        unlink($path);
    }

    return true;
}
