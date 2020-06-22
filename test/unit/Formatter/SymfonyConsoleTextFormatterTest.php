<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymfonyConsoleTextFormatter;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\sprintf;
use function uniqid;

/**
 * @covers \Roave\BackwardCompatibility\Formatter\SymfonyConsoleTextFormatter
 */
final class SymfonyConsoleTextFormatterTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testWrite(): void
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

        (new SymfonyConsoleTextFormatter($output))->write(Changes::fromList(
            Change::removed($change1Text, true),
            Change::added($change2Text, false)
        ));
    }
}
