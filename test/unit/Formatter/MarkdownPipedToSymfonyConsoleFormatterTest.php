<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\MarkdownPipedToSymfonyConsoleFormatter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Roave\BackwardCompatibility\Formatter\MarkdownPipedToSymfonyConsoleFormatter
 */
final class MarkdownPipedToSymfonyConsoleFormatterTest extends TestCase
{
    public function testWrite() : void
    {
        $output = $this->createMock(OutputInterface::class);

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

        $output->expects(self::any())
            ->method('writeln')
            ->willReturnCallback(static function (string $output) use ($changeToExpect) : void {
                self::assertContains($changeToExpect, $output);
            });

        (new MarkdownPipedToSymfonyConsoleFormatter($output))->write(Changes::fromList(
            Change::added('Something added', true),
            Change::added('Something added', false),
            Change::changed('Something changed', true),
            Change::changed('Something changed', false),
            Change::removed('Something removed', true),
            Change::removed('Something removed', false)
        ));
    }
}
