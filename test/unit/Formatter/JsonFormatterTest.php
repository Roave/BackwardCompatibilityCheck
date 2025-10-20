<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psl\Env;
use Psl\File;
use Psl\Filesystem;
use Psl\Json;
use ReflectionException;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\JsonFormatter;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Symfony\Component\Console\Output\BufferedOutput;

#[CoversClass(JsonFormatter::class)]
final class JsonFormatterTest extends TestCase
{
    /** @throws ReflectionException */
    public function testWrite(): void
    {
        $output            = new BufferedOutput();
        $temporaryLocation = Filesystem\create_temporary_file(Env\temp_dir(), 'jsonFormatter');

        Filesystem\delete_file($temporaryLocation);
        Filesystem\create_directory($temporaryLocation . '/foo/bar/.git');

        (new JsonFormatter(
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

        $expected = [
            'errors' => [
                ['description' => 'foo', 'path' => null, 'line' => null, 'column' => null, 'modificationType' => 'removed'],
                ['description' => 'bar', 'path' => null, 'line' => null, 'column' => null, 'modificationType' => 'added'],
                ['description' => 'baz', 'path' => 'baz-file.php', 'line' => null, 'column' => null, 'modificationType' => 'changed'],
                ['description' => 'tab', 'path' => 'tab-file.php', 'line' => 5, 'column' => null, 'modificationType' => 'changed'],
                ['description' => 'taz', 'path' => 'taz-file.php', 'line' => 6, 'column' => 15, 'modificationType' => 'changed'],
                ['description' => 'tar', 'path' => 'tar-file.php', 'line' => -1, 'column' => -1, 'modificationType' => 'changed'],
                ['description' => 'file-in-checked-out-dir', 'path' => 'subpath/file-in-checked-out-dir.php', 'line' => 10, 'column' => 20, 'modificationType' => 'changed'],
            ],
        ];

        $json = $output->fetch();
        self::assertJson($json);

        $data = Json\decode($json);
        self::assertIsArray($data);
        self::assertEquals($expected, $data);
        
        /** @var mixed $content */
        $content = Json\decode($json, false);
        
        $validator = new Validator();
        
        $validator->validate(
            $content,
            Json\decode(File\read(__DIR__ . '/../../../Resources/errors.schema.json')),
        );

        self::assertEmpty($validator->getErrors());
        self::assertJsonStringEqualsJsonString(
            <<<'OUTPUT'
{"errors":[{"description":"foo","path":null,"line":null,"column":null,"modificationType":"removed"},{"description":"bar","path":null,"line":null,"column":null,"modificationType":"added"},{"description":"baz","path":"baz-file.php","line":null,"column":null,"modificationType":"changed"},{"description":"tab","path":"tab-file.php","line":5,"column":null,"modificationType":"changed"},{"description":"taz","path":"taz-file.php","line":6,"column":15,"modificationType":"changed"},{"description":"tar","path":"tar-file.php","line":-1,"column":-1,"modificationType":"changed"},{"description":"file-in-checked-out-dir","path":"subpath\/file-in-checked-out-dir.php","line":10,"column":20,"modificationType":"changed"}]}

OUTPUT
            ,
            $json,
        );
    }
}
