<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare\Formatter;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\MarkdownPipedToSymfonyConsoleFormatter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Roave\ApiCompare\Formatter\MarkdownPipedToSymfonyConsoleFormatter
 */
final class MarkdownFormatterTest extends TestCase
{
    public function testWrite() : void
    {
        $markdownFilename = tempnam(sys_get_temp_dir(), uniqid('api-compare-', true)) . '.md';

        (new MarkdownPipedToSymfonyConsoleFormatter($markdownFilename))->write(Changes::fromArray([
            Change::added('Something added', true),
            Change::added('Something added', false),
            Change::changed('Something changed', true),
            Change::changed('Something changed', false),
            Change::removed('Something removed', true),
            Change::removed('Something removed', false),
        ]));

        $changeToExpect = <<<EOF
# Added
 - [BC] Something added
 - Something added

# Changed
 - [BC] Something changed
 - Something changed

# Removed
 - [BC] Something removed
 - Something removed

EOF;

        self::assertSame($changeToExpect, file_get_contents($markdownFilename));
        unlink($markdownFilename);
    }
}
