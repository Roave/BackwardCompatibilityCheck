<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare\Formatter;

use Roave\ApiCompare\Change;
use Roave\ApiCompare\Changes;
use Roave\ApiCompare\Formatter\SymfonyConsoleTextFormatter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Roave\ApiCompare\Formatter\SymfonyConsoleTextFormatter
 */
final class SymfonyConsoleTextFormatterTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testWrite() : void
    {
        $change1Text = uniqid('change1', true);
        $change2Text = uniqid('change2', true);

        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::at(0))
            ->method('writeln')
            ->with(sprintf('[BC] REMOVED: %s', $change1Text));
        $output->expects(self::at(1))
            ->method('writeln')
            ->with(sprintf('     ADDED: %s', $change2Text));

        (new SymfonyConsoleTextFormatter($output))->write(Changes::fromArray([
            Change::removed($change1Text, true),
            Change::added($change2Text, false),
        ]));
    }
}
