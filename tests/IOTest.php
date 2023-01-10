<?php
namespace Pyncer\Tests\Core;

use PHPUnit\Framework\TestCase;

use const DIRECTORY_SEPARATOR as DS;

class IOTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = __DIR__ . DS . 'Assets';

        if (!is_dir($this->dir)) {
            mkdir($this->dir);
        }

        if (!is_dir($this->dir . DS . 'Dir1')) {
            mkdir($this->dir . DS . 'Dir1');
        }

        if (!is_dir($this->dir . DS . 'Dir2')) {
            mkdir($this->dir . DS . 'Dir2');
        }

        file_put_contents($this->dir . DS . 'empty.txt', '');
        file_put_contents($this->dir . DS . 'file.csv', 'test');
        file_put_contents($this->dir . DS . 'unicode.txt', 'テスト');
    }

    protected function tearDown(): void
    {
        unlink($this->dir . DS . 'empty.txt');
        unlink($this->dir . DS . 'file.csv');
        unlink($this->dir . DS . 'unicode.txt');

        if (is_dir($this->dir . DS . 'Dir1')) {
            rmdir($this->dir . DS . 'Dir1');
        }

        if (is_dir($this->dir . DS . 'Dir2')) {
            rmdir($this->dir . DS . 'Dir2');
        }

        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
    }

    public function testFiles(): void
    {
        $files = \Pyncer\IO\files($this->dir);
        $this->assertEquals(count($files), 3);
        $this->assertEquals($files[0]['basename'], 'empty.txt');

        $files = \Pyncer\IO\files($this->dir, 'txt');
        $this->assertEquals(count($files), 2);
    }

    public function testFilenames(): void
    {
        $filenames = \Pyncer\IO\filenames($this->dir);
        $this->assertEquals($filenames[0], 'empty.txt');
        $this->assertEquals($filenames[1], 'file.csv');
        $this->assertEquals($filenames[2], 'unicode.txt');

        $filenames = \Pyncer\IO\filenames($this->dir, 'csv', true);
        $this->assertEquals($filenames[0], 'file');

        $filenames = \Pyncer\IO\filenames($this->dir, ['txt']);
        $this->assertEquals($filenames[0], 'empty.txt');
        $this->assertEquals($filenames[1], 'unicode.txt');
    }

    public function testFilename(): void
    {
        $filename = \Pyncer\IO\filename($this->dir . DS . 'empty.txt');
        $this->assertEquals($filename, 'empty');
    }

    public function testExtension(): void
    {
        $extension = \Pyncer\IO\extension($this->dir . DS . 'empty.txt');
        $this->assertEquals($extension, 'txt');

        $extension = \Pyncer\IO\extension($this->dir . DS . 'empty');
        $this->assertEquals($extension, null);
    }

    public function testReplaceExtension(): void
    {
        $filename = \Pyncer\IO\replace_extension('file.txt', 'csv');
        $this->assertEquals($filename, 'file.csv');
    }

    public function testFilesizeFromString(): void
    {
        $size = \Pyncer\IO\filesize_from_string('');
        $this->assertEquals($size, 0);

        $size = \Pyncer\IO\filesize_from_string('test');
        $this->assertEquals($size, 4);

        $size = \Pyncer\IO\filesize_from_string('テスト');
        $this->assertEquals($size, 9);
    }

    public function testWriteFile(): void
    {
        $file = $this->dir . DS . 'test.txt';

        \Pyncer\IO\write_file($file, 'test');
        $this->assertEquals(file_get_contents($file), 'test');

        \Pyncer\IO\write_file($file, 'test', true);
        $this->assertEquals(file_get_contents($file), 'testtest');

        unlink($file);
    }

    public function testReadFile(): void
    {
        $file = $this->dir . DS . 'unicode.txt';

        $data = \Pyncer\IO\read_file($file);
        $this->assertEquals($data, 'テスト');

        $data = \Pyncer\IO\read_file($file, 3, 3);
        $this->assertEquals($data, 'ス');
    }

    public function testDownloadFile(): void
    {
        $file = $this->dir . DS . 'unicode.txt';
        $dir = $this->dir . DS . 'Dir1';

        $downloadedFile = \Pyncer\IO\download_file($file, $dir);

        $this->assertEquals($downloadedFile, $dir . DS . 'unicode.txt');
        $this->assertEquals(file_get_contents($downloadedFile), 'テスト');

        unlink($downloadedFile);

        $downloadedFile = \Pyncer\IO\download_file($file, $dir, 'newfile.txt');

        $this->assertEquals($downloadedFile, $dir . DS . 'newfile.txt');
        $this->assertEquals(file_get_contents($downloadedFile), 'テスト');

        unlink($downloadedFile);
    }

    public function testDirs(): void
    {
        $dirs = \Pyncer\IO\dirs($this->dir);
        $this->assertEquals(count($dirs), 2);
        $this->assertEquals($dirs[0]['basename'], 'Dir1');
    }

    public function testDirnames(): void
    {
        $dirnames = \Pyncer\IO\dirnames($this->dir);
        $this->assertEquals($dirnames[0], 'Dir1');
        $this->assertEquals($dirnames[1], 'Dir2');
    }

    public function testIsEmpty(): void
    {
        $dirFull = $this->dir . DS . 'Full';
        mkdir($dirFull);

        $file = $dirFull . DS . 'file.txt';
        file_put_contents($file, 'file');

        $dirEmpty = $this->dir . DS . 'Empty';
        mkdir($dirEmpty);

        $this->assertFalse(\Pyncer\IO\is_empty($dirFull));
        $this->assertTrue(\Pyncer\IO\is_empty($dirEmpty));

        unlink($file);
        rmdir($dirFull);
        rmdir($dirEmpty);
    }

    public function testCopy(): void
    {
        $from = $this->dir . DS . 'unicode.txt';
        $to = $this->dir . DS . 'copied.txt';
        \Pyncer\IO\copy($from, $to);

        $this->assertTrue(file_exists($to));

        $from = $this->dir . DS . 'file.csv';
        $to = $this->dir . DS . 'copied.txt';
        \Pyncer\IO\copy($from, $to, true);

        $this->assertEquals(file_get_contents($to), 'test');

        if (file_exists($to)) {
            unlink($to);
        }
    }

    public function testMove(): void
    {
        $from = $this->dir . DS . 'moveFrom.txt';
        $to = $this->dir . DS . 'moveTo.txt';
        file_put_contents($from, 'test');

        \Pyncer\IO\move($from, $to);
        $this->assertTrue(file_exists($to));

        file_put_contents($from, 'test2');
        \Pyncer\IO\move($from, $to, true);
        $this->assertEquals(file_get_contents($to), 'test2');

        if (file_exists($from)) {
            unlink($from);
        }

        if (file_exists($to)) {
            unlink($to);
        }
    }

    public function testRename(): void
    {
        $from = $this->dir . DS . 'renameFrom.txt';
        $to = $this->dir . DS . 'renameTo.txt';
        file_put_contents($from, 'test');

        \Pyncer\IO\rename($from, $to);
        $this->assertTrue(file_exists($to));

        if (file_exists($from)) {
            unlink($from);
        }

        if (file_exists($to)) {
            unlink($to);
        }
    }

    public function testDeleteContents(): void
    {
        $dir = $this->dir . DS . 'Delete';
        mkdir($dir);

        $file = $dir . DS . 'delete.txt';
        file_put_contents($file, 'delete');

        \Pyncer\IO\delete_contents($dir);

        $this->assertFalse(file_exists($dir . DS . 'delete.txt'));
        $this->assertTrue(file_exists($dir));

        if (file_exists($file)) {
            unlink($file);
        }

        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    public function testDelete(): void
    {
        $dir = $this->dir . DS . 'Delete';
        mkdir($dir);

        $file1 = $dir . DS . 'delete1.txt';
        file_put_contents($file1, 'delete1');

        $file2 = $dir . DS . 'delete2.txt';
        file_put_contents($file2, 'delete2');

        \Pyncer\IO\delete($file2);
        $this->assertFalse(file_exists($file2));

        \Pyncer\IO\delete($dir);
        $this->assertFalse(is_dir($dir));

        if (file_exists($file1)) {
            unlink($file1);
        }

        if (file_exists($file2)) {
            unlink($file2);
        }

        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    public function testDeleteMatching(): void
    {
        // Delete
        $dirDelete = $this->dir . DS . 'Delete';
        mkdir($dirDelete);

        $dirDelete1 = $this->dir . DS . 'Delete' . DS . 'Dir1';
        mkdir($dirDelete1);

        $dirDelete2 = $this->dir . DS . 'Delete' . DS . 'Dir2';
        mkdir($dirDelete2);

        $fileDelete1 = $dirDelete . DS . 'file1.txt';
        file_put_contents($fileDelete1, 'test');

        $fileDelete2 = $dirDelete . DS . 'file2.txt';
        file_put_contents($fileDelete2, 'test');

        // Match
        $dirMatch = $this->dir . DS . 'Match';
        mkdir($dirMatch);

        $dirMatch1 = $this->dir . DS . 'Match' . DS . 'Dir1';
        mkdir($dirMatch1);

        $dirMatch3 = $this->dir . DS . 'Match' . DS . 'Dir3';
        mkdir($dirMatch3);

        $fileMatch1 = $dirMatch. DS . 'file1.txt';
        file_put_contents($fileMatch1, 'test');

        $fileMatch3 = $dirMatch. DS . 'file3.txt';
        file_put_contents($fileMatch3, 'test');

        \Pyncer\IO\delete_matching($dirDelete, $dirMatch);

        $this->assertFalse(file_exists($dirDelete1));
        $this->assertTrue(file_exists($dirDelete2));
        $this->assertFalse(file_exists($fileDelete1));
        $this->assertTrue(file_exists($fileDelete2));

        if (file_exists($fileMatch1)) {
            unlink($fileMatch1);
        }

        if (file_exists($fileMatch3)) {
            unlink($fileMatch3);
        }

        if (is_dir($dirMatch1)) {
            rmdir($dirMatch1);
        }

        if (is_dir($dirMatch3)) {
            rmdir($dirMatch3);
        }

        if (is_dir($dirMatch)) {
            rmdir($dirMatch);
        }

        if (file_exists($fileDelete1)) {
            unlink($fileDelete1);
        }

        if (file_exists($fileDelete2)) {
            unlink($fileDelete2);
        }

        if (is_dir($dirDelete1)) {
            unlink($dirDelete1);
        }

        if (is_dir($dirDelete2)) {
            rmdir($dirDelete2);
        }

        if (is_dir($dirDelete)) {
            rmdir($dirDelete);
        }
    }

    public function testMakeDir(): void
    {
        $dir = $this->dir . DS . 'make' . DS . 'dir';
        \Pyncer\IO\make_dir($dir);

        $this->assertTrue(is_dir($dir));

        rmdir($dir);

        $dir = dirname($dir);

        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    public function testCleanDir(): void
    {
        $dir = '/a/b/$Extend/?<c/';
        $dir = \Pyncer\IO\clean_dir($dir);
        $this->assertEquals(
            $dir,
            DS . 'a' . DS . 'b' . DS . '_' . DS . '__c'
        );

        $dir = 'C:/a/b/$Extend/?<c/';
        $dir = \Pyncer\IO\clean_dir($dir);
        $this->assertEquals(
            $dir,
            'C:' . DS . 'a' . DS . 'b' . DS . '_' . DS . '__c'
        );
    }

    public function testCleanPath(): void
    {
        $path = \Pyncer\IO\clean_path('/');
        $this->assertEquals($path, '');

        $path = \Pyncer\IO\clean_path('/$Extend/?/');
        $this->assertEquals($path, DS . '_' . DS . '_');
    }

    public function testIsValidPath(): void
    {
        $isValid = \Pyncer\IO\is_valid_path('/');
        $this->assertTrue($isValid);

        $isValid = \Pyncer\IO\is_valid_path('/a');
        $this->assertTrue($isValid);

        $isValid = \Pyncer\IO\is_valid_path('/a/');
        $this->assertTrue($isValid);

        $isValid = \Pyncer\IO\is_valid_path('/?/');
        $this->assertFalse($isValid);

        $isValid = \Pyncer\IO\is_valid_path('/$Extend');
        $this->assertFalse($isValid);
    }

    public function testJoinPaths(): void
    {
        $path = \Pyncer\IO\join_paths(
            '/a',
            'b/',
            '/c/',
            'd',
        );

        $this->assertEquals(
            $path,
            DS . 'a' . DS . 'b' . DS . 'c' . DS . 'd'
        );
    }

    public function testCleanFilename(): void
    {
        $filename = \Pyncer\IO\clean_filename('?');
        $this->assertEquals($filename, '_');

        $filename = \Pyncer\IO\clean_filename('$Reparse');
        $this->assertEquals($filename, '_');
    }
}
