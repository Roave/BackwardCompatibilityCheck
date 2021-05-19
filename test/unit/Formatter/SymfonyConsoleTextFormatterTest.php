<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Formatter;

use PHPUnit\Framework\TestCase;
use ReflectionException;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Formatter\SymfonyConsoleTextFormatter;
use Symfony\Component\Console\Output\OutputInterface;
use Psl\Str;
use Psl\SecureRandom;

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
        $change1Text = SecureRandom\string(8);
        $change2Text = SecureRandom\string(8);

        $output = $this->createMock(OutputInterface::class);
        $output->expects(self::at(0))
            ->method('writeln')
            ->with(Str\format('[BC] REMOVED: %s', $change1Text));
        $output->expects(self::at(1))
            ->method('writeln')
            ->with(Str\format('     ADDED: %s', $change2Text));

        (new SymfonyConsoleTextFormatter($output))->write(Changes::fromList(
            Change::removed($change1Text, true),
            Change::added($change2Text, false)
        ));
    }
}
