<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare\Formatter;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\MarkdownPipedToSymfonyConsoleFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Roave\ApiCompare\Formatter\MarkdownPipedToSymfonyConsoleFormatter
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
            ->willReturnCallback(function (string $output) use ($changeToExpect) {
                self::assertContains($changeToExpect, $output);
            });

        (new MarkdownPipedToSymfonyConsoleFormatter($output))->write(Changes::fromArray([
            Change::added('Something added', true),
            Change::added('Something added', false),
            Change::changed('Something changed', true),
            Change::changed('Something changed', false),
            Change::removed('Something removed', true),
            Change::removed('Something removed', false),
        ]));
    }
}
