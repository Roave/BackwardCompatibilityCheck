<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use Psl\Env;
use Psl\Filesystem;
use ReflectionException;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\JunitFormatter;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Symfony\Component\Console\Output\BufferedOutput;

use function extension_loaded;

/** @covers \Roave\BackwardCompatibility\Formatter\JunitFormatter */
final class JunitFormatterTest extends TestCase
{
    /** @throws ReflectionException */
    public function testWrite(): void
    {
        $output            = new BufferedOutput();
        $temporaryLocation = Filesystem\create_temporary_file(Env\temp_dir(), 'JunitFormatter');

        Filesystem\delete_file($temporaryLocation);
        Filesystem\create_directory($temporaryLocation . '/foo/bar/.git');

        (new JunitFormatter(
            $output,
            CheckedOutRepository::fromPath($temporaryLocation . '/foo/bar'),
        ))->write(Changes::fromList(
            Change::removed('foo', true),
            Change::added('bar', false),
            Change::changed('baz', false)
                ->onFile('baz-file.php'),
            Change::changed('tab', false)
                ->onFile('tab-file.php')
                ->onLine(5),
            Change::changed('taz', false)
                ->onFile('taz-file.php')
                ->onLine(6)
                ->onColumn(15),
            Change::changed('tar', false)
                ->onFile('tar-file.php')
                ->onLine(-1)
                ->onColumn(-1),
            Change::changed('file-in-checked-out-dir', false)
                ->onFile($temporaryLocation . '/foo/bar/subpath/file-in-checked-out-dir.php')
                ->onLine(10)
                ->onColumn(20),
        ));

        Filesystem\delete_directory($temporaryLocation, true);

        $fetchedOutput = $output->fetch();

        self::assertSame(
            <<<'OUTPUT'
<?xml version="1.0" encoding="UTF-8"?>
<testsuite name="roave/backward-compatibility-check" tests="7" failures="7" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.1/platform-tests/src/test/resources/jenkins-junit.xsd">
  <testcase name="::"><failure type="error" message="[BC] REMOVED: foo"/></testcase>
  <testcase name="::"><failure type="error" message="ADDED: bar"/></testcase>
  <testcase name="baz-file.php::"><failure type="error" message="CHANGED: baz"/></testcase>
  <testcase name="tab-file.php:5:"><failure type="error" message="CHANGED: tab"/></testcase>
  <testcase name="taz-file.php:6:15"><failure type="error" message="CHANGED: taz"/></testcase>
  <testcase name="tar-file.php:-1:-1"><failure type="error" message="CHANGED: tar"/></testcase>
  <testcase name="subpath/file-in-checked-out-dir.php:10:20"><failure type="error" message="CHANGED: file-in-checked-out-dir"/></testcase>
</testsuite>

OUTPUT
            ,
            $fetchedOutput,
        );

        if (! extension_loaded('dom')) {
            return;
        }

        $dom = new DOMDocument();
        $dom->loadXML($fetchedOutput);

        self::assertTrue(
            $dom->schemaValidate(__DIR__ . '/junit.xsd'),
        );
    }
}
