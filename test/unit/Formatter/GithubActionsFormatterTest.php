<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use PHPUnit\Framework\TestCase;
use Psl\Env;
use Psl\Filesystem;
use ReflectionException;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\GithubActionsFormatter;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Symfony\Component\Console\Output\BufferedOutput;

/** @covers \Roave\BackwardCompatibility\Formatter\GithubActionsFormatter */
final class GithubActionsFormatterTest extends TestCase
{
    /** @throws ReflectionException */
    public function testWrite(): void
    {
        $output            = new BufferedOutput();
        $temporaryLocation = Filesystem\create_temporary_file(Env\temp_dir(), 'githubActionsFormatter');

        Filesystem\delete_file($temporaryLocation);
        Filesystem\create_directory($temporaryLocation . '/foo/bar/.git');

        (new GithubActionsFormatter(
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

        self::assertEquals(
            <<<'OUTPUT'
::error::foo
::error::bar
::error file=baz-file.php,line=1,col=0::baz
::error file=tab-file.php,line=5,col=0::tab
::error file=taz-file.php,line=6,col=15::taz
::error file=tar-file.php,line=-1,col=-1::tar
::error file=subpath/file-in-checked-out-dir.php,line=10,col=20::file-in-checked-out-dir

OUTPUT
            ,
            $output->fetch(),
        );

        Filesystem\delete_directory($temporaryLocation, true);
    }
}
