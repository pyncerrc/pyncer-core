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
use function umask;
use function unlink;
use function uniqid;

use const DIRECTORY_SEPARATOR as DS;
use const Pyncer\IO\MODE_DIR as PYNCER_IO_MODE_DIR;
use const Pyncer\IO\MODE_FILE as PYNCER_IO_MODE_FILE;
use const Pyncer\IO\DIR_ROOT as PYNCER_IO_DIR_ROOT;
use const Pyncer\IO\BAD_PATH_CHARS as PYNCER_IO_BAD_PATH_CHARS;
use const Pyncer\IO\BAD_PATHS as PYNCER_IO_BAD_PATHS;
use const Pyncer\IO\BAD_FILENAME_CHARS as PYNCER_IO_BAD_FILENAME_CHARS;
use const Pyncer\IO\BAD_FILENAMES as PYNCER_IO_BAD_FILENAMES;

function files(string $dir, null|string|array $extensions = null): array
{
    $files = [];

    $dir = clean_dir($dir);
    $extensions = pyncer_ensure_array($extensions, [null, '']);

    if (is_dir($dir) && $handle = opendir($dir)) {
        while (($path = readdir($handle)) !== false) {
            // Skip the reference to current and parent directory
            if ($path != '.' && $path != '..' && !is_dir($dir . DS . $path)) {
                $info = pathinfo($dir . DS . $path);
                if (!$extensions || in_array(strtolower($info['extension']), $extensions)) {
                    $files[] = $info;
                }
            }
        }
        closedir($handle);
    }

    return $files;
}
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

function filename(string $file): ?string
{
    $pos = strrpos($file, DS);
    if ($pos !== false) {
        $file = substr($file, $pos + strlen(DS));
    }

    $pos = strrpos($file, '.');
    if ($pos !== false) {
        $file = substr($file, 0, $pos);
    }

    return ($file !== '' ? $file : null);
}

function extension(string $file): ?string
{
    $pos = strrpos($file, '.');

    if ($pos !== false) {
        return substr($file, $pos + 1);
    }

    return null;
}

function replace_extension(string $file, ?string $extension = null): string
{
    $pos = strrpos($file, '.');
    if ($pos !== false) {
        $file = substr($file, 0, $pos);
    }

    if ($extension !== null) {
        $file .= '.' . ltrim($extension, '.');
    }

    return $file;
}

function filesize_from_string(string $s): int
{
    return mb_strlen($s, '8bit');
}

function write_file(
    string $file,
    string $s,
    bool $append = false,
    ?int $mode = null
): void
{
    $dirBase = dirname($file);
    if (!file_exists($dirBase)) {
        make_dir($dirBase);
    }

    if (!file_put_contents($file, $s, ($append ? FILE_APPEND : 0)) !== false) {
        throw new RuntimeException('File is not writeable.');
    }

    if ($mode !== null) {
        @php_chmod($file, $mode);
    }
}
function read_file(string $file, int $length = 0, int $offset = 0): string
{
    if (!file_exists($file)) {
        throw new RuntimeException('File not found.');
    }

    if (!is_readable($file)) {
        throw new RuntimeException('File is not readable.');
    }

    $handle = fopen($file, 'rb');

    if (!$handle) {
        throw new RuntimeException('File is not readable.');
    }

    clearstatcache(false, $file);

    if ($offset) {
        fseek($handle, $offset);
    }

    $s = '';
    $amount = 0;
    $readLength = min($length, 4096);
    while (!feof($handle) && (!$length || $amount < $length)) {
        $s .= fread($handle, $readLength);
        $amount += 4096;
    }

    fclose($close);

    return $s;
}

function download_file(
    string $sourceFileUrl,
    string $destinationDir,
    string $filename = '',
    ?int $mode = null
): string
{
    // Set pyncer as the user agent
    ini_set('user_agent', 'Pyncer');

    $handle = fopen($sourceFileUrl, 'r');
    if (!$handle) {
        throw new RuntimeException('Source file is not readable.');
    }

    if (!$filename) {
        // Check header for filename if not specified
        $metaData = stream_get_meta_data($handle);
        foreach ($metaData['wrapper_data'] as $wrapper_data) {
            if (substr($wrapper_data, 0, 19) == 'Content-Disposition') {
                $filename = explode ('"', $wrapper_data);
                $filename = $filename[1];
                break;
            }
        }
    }

    // TODO: Make this less stupid and more efficent
    // ie. write inline as you read so not taking up memory

    // Set filename if not specified
    if (!$filename) {
        $parts = explode('/', $sourceFileUrl);
        $filename = $parts[count($parts) - 1];
    }

    // If still no filename then source file it is a directory
    if (!$filename) {
        fclose($handle);
        throw new RuntimeException('Source file not found.');
    }

    $data = '';
    while (!feof($handle)) {
        $dataPart = fread($handle, 4096);

        if (!$dataPart) {
            fclose($handle);
            throw new RuntimeException('Source file is not readable.');
        }

        $data .= $dataPart;
    }

    $destinationDir = clean_dir($destinationDir);
    $file = $destinationDir . DS . $filename;

    try {
        write($file, $data, $mode);
    } finally {
        fclose($handle);
    }

    return $file;
}
function move_uploaded_file(
    string $sourceFile,
    string $destFile,
    ?int $mode = null
): void
{
    $dirBase = dirname($destFile);
    if (!file_exists($dirBase)) {
        make_dir($dirBase);
    }

    if (!php_move_uploaded_file($sourceFile, $destFile)) {
        throw new RuntimeException('Uploaded file could not be moved.');
    }

    if ($mode !== null) {
        @php_chmod($destFile, $mode);
    }
}

function dirs(string $dir): array
{
    $dirs = [];

    $dir = clean_dir($dir);
    if (is_dir($dir) && $handle = opendir($dir)) {
        while (($path = readdir($handle)) !== false) {
            // Skip the reference to current and parent directory
            if ($path != '.' && $path != '..' && is_dir($dir . DS . $path)) {
                $dirs[] = pathinfo($dir . DS . $path);
            }
        }
        closedir($handle);
    }

    return $dirs;
}
function dirnames(string $dir): array
{
    $dirs = [];

    $dirsInfo = dirs($dir);
    foreach ($dirsInfo as $value) {
        $dirs[] = $value['basename'];
    }

    return $dirs;
}

function is_empty(string $dir, array $ignore = []): bool
{
    $dir = clean_dir($dir);

    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return false;
    }

    if ($handle = opendir($dir)) {
        while (($path = readdir($handle)) !== false) {
            if ($path != '.' && $path != '..' && !in_array($path, $ignore)) {
                closedir($handle);
                return false;
            }
        }
        closedir($handle);
    }

    return true;
}

function copy(string $src, string $dest, bool $overwrite = false): void
{
    $src = clean_dir($src);

    if (!file_exists($src)) {
        throw new RuntimeException('Source not found.');
    }

    // If the src is a directory then copy all contents to
    // destination which is also a directory
    if (is_dir($src) && !is_link($src)) {
        $src = rtrim($src, DS);
        $dest = clean_dir($dest);

        if (!file_exists($dest)) {
            make_dir($dest);
        } elseif (!is_dir($dest)) {
            throw new RuntimeException('Destination is not a directory.');
        }

        $handle = opendir($src);

        while (($path = readdir($handle)) !== false) {
            if ($path === '.' || $path === '..') {
                continue;
            }

            switch (filetype($src . DS . $path)) {
                case 'dir':
                case 'file':
                case 'link':
                    copy(
                        $src . DS . $path,
                        $dest . DS . $path, $overwrite
                    );
            }
        }

        closedir($handle);

        return;
    }

    // If the src is a file, then copy its contents to
    // destination which is also a file
    $dest = clean_dir($dest);
    if (is_dir($dest) && !is_link($dest)) {
        throw new RuntimeException('Destination is not a directory.');
    }

    $destinationDir = dirname($dest);
    if (!file_exists($destinationDir)) {
        make_dir($destinationDir);
    }

    if (!$overwrite && file_exists($dest)) {
        throw new RuntimeException('Destination file already exists.');
    }

    if (!php_copy($src, $dest)) {
        throw new RuntimeException('Source could not be copied.');
    }
}

function move(string $src, string $dest, bool $overwrite = false): void
{
    $src = clean_dir($src);

    if (!file_exists($src)) {
        throw new RuntimeException('Source does not exist.');
    }

    // If the src is a directory then copy all contents to
    // destination which is also a directory
    if (is_dir($src) && !is_link($src)) {
        $src = rtrim($src, DS);
        $dest = clean_dir($dest);

        if (!file_exists($dest)) {
            make_dir($dest);
        } elseif (!is_dir($dest)) {
            throw new RuntimeException('Destination is not a directory.');
        }

        $handle = opendir($src);
        while (($path = readdir($handle)) !== false) {
            if ($path === '.' || $path === '..') {
                continue;
            }

            switch (filetype($src . DS . $path)) {
                case 'dir':
                case 'file':
                case 'link':
                    move(
                        $src . DS . $path,
                        $dest . DS . $path, $overwrite
                    );
            }
        }
        closedir($handle);

        // Delete src directory if it is empty
        if (is_empty($src)) {
            delete($src);
        }

        return;
    }

    rename($src, $dest, $overwrite);
}

/**
* Renames a file or directory, if overwrite is true, and the
* destination file or directory already exists, it will be deleted
* before renaming the source.
*
* @param string $src The file or directory to rename
* @param string $dest The destination file or directory
* @param bool $overwrite Set to true to overwrite the destinatoin directory
*/
function rename(
    string $sourceFile,
    string $destinationDir,
    bool $overwrite = false
): void
{
    if (!file_exists($sourceFile)) {
        throw new RuntimeException('Source does not exist.');
    }

    if (file_exists($destinationDir)) {
        if (!$overwrite) {
            throw new RuntimeException('Destination already exists.');
        }

        // Only overwrite the same types
        if (is_dir($sourceFile) && !is_link($sourceFile)) {
            if (!is_dir($destinationDir)) {
                throw new RuntimeException('Destination is not a directory.');
            }
        } elseif (is_dir($destinationDir) && !is_link($destinationDir)) {
            throw new RuntimeException('Destination is not a file.');
        }

        if ($overwrite) {
            delete($destinationDir);
        }
    } else {
        // Ensure the parent rename directory exists
        $dir = dirname($destinationDir);
        make_dir($dir);
    }

    if (!php_rename($sourceFile, $destinationDir)) {
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

    if ($handle = opendir($file)) {
        while (($path = readdir($handle)) !== false) {
            if ($path != '.' && $path != '..') {
                delete($file . DS . $path);
            }
        }
        closedir($handle);
    }

    if (!rmdir($file)) {
        throw new RuntimeException('Dir could not be deleted.');
    }
}

/**
* Deletes all files and directories that match the structure
* of the $dir_match directory.
*
* @param string $dir_src Dir to delete files in
* @param string $dir_match Dir to match against its structure
*/
function delete_matching(string $sourceDir, string $matchDir): void
{
    $sourceDir = clean_dir($sourceDir);
    if (!file_exists($sourceDir)) {
        throw new RuntimeException('Source directory does not exist.');
    }

    $matchDir = clean_dir($matchDir);
    if (!file_exists($matchDir)) {
        throw new RuntimeException('Match directory does not exist.');
    }

    if (!is_dir($sourceDir) || is_link($sourceDir)) {
        throw new RuntimeException('Source directory is not a directory.');
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

        if (file_exists($sourceDir . $path)) {
            delete($sourceDir . DS . $path);
        }
    }
    closedir($handle);
}

function make_dir(string $dir, int $mode = PYNCER_IO_MODE_DIR): void
{
    $dirBase = clean_dir(PYNCER_IO_DIR_ROOT);
    if ($dirBase) { // Clean if base directory is set.
        $lenDir = pyncer_str_len($dirBase);
        if (pyncer_str_sub($dir, 0, $lenDir) == $dirBase) {
            $dir = pyncer_str_sub($dir, $lenDir);
        } else {
            $dirBase = '';
        }
    }

    $paths = explode(DS, $dir);

    // If no base dir, and an empty path[0] then
    // its a linux style path starting with /
    if (!$dirBase) {
        $dirBase = $paths[0];
        unset($paths[0]);
    }

    $origionalMask = umask(0);

    foreach ($paths as $path) {
        if ($path) {
            $dirBase .= DS . $path;
            if (!file_exists($dirBase)) {
                if (!mkdir($dirBase)) {
                    echo $dirBase;
                    umask($origionalMask);
                    throw new RuntimeException('Dir could not be made.');
                }

                @php_chmod($dirBase, $mode);
            } else {
                // Dir already exists, update mod
                @php_chmod($dirBase, $mode);
            }
        }
    }

    umask($origionalMask);
}

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
    ?int $fileMode = PYNCER_IO_MODE_FILE,
    ?int $dirMode = PYNCER_IO_MODE_DIR
): void
{
    if (is_link($file)) {
        throw new RuntimeException('Symbolic link could not have its mode changed.');
    }

    $dir = clean_dir($file);
    if (is_dir($dir)) {
        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                // We do not want to change the permissions of linked paths
                if (is_link($dir . DS . $file)) {
                    throw new RuntimeException('Symbolic link could not have its mode changed.');
                }

                if (is_dir($dir . DS . $file)) {
                    chmod(
                        $dir . DS . $file,
                        $fileMode,
                        $dirMode
                    );
                    continue;
                }

                if ($fileMode !== null &&
                    !@php_chmod($dir . DS . $file, $fileMode)
                ) {
                    throw new RuntimeException('File could not have its mode changed.');
                }
            }
        }

        closedir($handle);

        if ($dirMode !== null &&
            !@php_chmod($dir, $dirMode)
        ) {
            throw new RuntimeException('File could not have its mode changed.');
        }
    }

    // $path is a file
    if ($fileMode !== false) {
        if (!@php_chmod($file, $fileMode)) {
            throw new RuntimeException('File could not have its mode changed.');
        }
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
    if ($parts[0] !== '' && !preg_match('/^[a-zA-Z]:\\$/', $parts[0])) {
        throw new InvalidArgumentException('Invalid directory.');
    }

    $parts[1] = clean_path($parts[1]);

    if ($parts[0] !== '') {
        return implode(DS, $parts);
    }

    return $parts[1];
}
/**
 * Cleans the specified relative path to the format used by the pyncer
 * ex. this/is/a/relative/path/
 *
 * @param string $path The path to clean
 */
function clean_path(string $path): string
{
    // Replace common slashes with directory separator
    if (DS !== '/') {
        $path = str_replace('/', DS, $path);
    }

    $parts = explode(DS, $path);
    $parts = pyncer_array_unset_empty($parts);

    // Remove invalid path characters
    foreach ($parts as $key => $value) {
        //$value = filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $value = preg_replace('/[^[:print:]]/', '', $value);
        $parts[$key] = preg_replace(
            '/[' . preg_quote(PYNCER_IO_BAD_PATH_CHARS, '/') . ']+/',
            '',
            $value
        );

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

    $path = DS . implode(DS, $parts);

    return ($path === clean_path($path));
}
function join_paths(string ...$paths): string
{
    static $badFilenameChars = '/\\?%*:|"<>';

    $paths = pyncer_array_unset_empty($paths);

    $joinedPaths = '';

    foreach ($paths as $value) {
        $joinedPaths .= clean_path($value);
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
* Removes characters not suxped in filenames by popular operating systems
* as well as renames the file name to an underscore if it matches a reserved file name.
*
* Note: This function is purely for making the file name safe for saving to disk,
* it does not take things like url characters into consideration
*/
function clean_filename(string $filename): string
{
    // Remove bad characters outright
    //$filename = filter_var($filename, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    $filename = preg_replace('/[^[:print:]]/', '', $filename);
    $filename = preg_replace(
        '/[' . preg_quote(PYNCER_IO_BAD_FILENAME_CHARS, '/') . ']+/',
        '',
        $filename
    );

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
*/
function is_writable(string $path): bool
{
    if (is_dir($path)) {
        $path = rtrim($path, DS);
        return is_writable($path . DS . uniqid(mt_rand()) . '.tmp');
    }

    // Check temporary file for read/write capabilities
    $rm = file_exists($path);
    $f = fopen($path, 'a');

    if ($f === false) {
        return false;
    }

    fclose($f);

    // Delete file if created for test
    if (!$rm) {
        unlink($path);
    }

    return true;
}
