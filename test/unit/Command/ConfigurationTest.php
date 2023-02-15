<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Command;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Baseline;
use Roave\BackwardCompatibility\Command\Configuration;
use RuntimeException;

/** @covers \Roave\BackwardCompatibility\Command\Configuration */
final class ConfigurationTest extends TestCase
{
    public function testBaselineShouldBeEmptyForDefaultConfiguration(): void
    {
        $config = Configuration::default();

        self::assertEquals(Baseline::empty(), $config->baseline);
    }

    /** @dataProvider validConfigurations */
    public function testBaselineShouldBeReadFromJsonContents(
        string $jsonContents,
        Baseline $expectedBaseline,
    ): void {
        $config = Configuration::fromJson($jsonContents);

        self::assertEquals($expectedBaseline, $config->baseline);
    }

    /** @psalm-return iterable<string, array{string, Baseline}> */
    public function validConfigurations(): iterable
    {
        yield 'empty object' => ['{}', Baseline::empty()];
        yield 'empty array' => ['[]', Baseline::empty()];
        yield 'empty baseline property' => ['{"baseline":[]}', Baseline::empty()];

        yield 'baseline with strings' => [
            <<<'JSON'
{"baseline": ["#\\[BC\\] CHANGED: The parameter \\$a#"]}
JSON,
            Baseline::fromList('#\[BC\] CHANGED: The parameter \$a#'),
        ];

        yield 'random properties are ignored' => [
            <<<'JSON'
{
  "baseline": ["#\\[BC\\] CHANGED: The parameter \\$a#"],
  "random": false
}
JSON,
            Baseline::fromList('#\[BC\] CHANGED: The parameter \$a#'),
        ];
    }

    /** @dataProvider invalidConfigurations */
    public function testExceptionShouldBeTriggeredOnInvalidConfiguration(
        string $jsonContents,
    ): void {
        $this->expectException(RuntimeException::class);

        Configuration::fromJson($jsonContents);
    }

    /** @psalm-return iterable<string, array{string}> */
    public function invalidConfigurations(): iterable
    {
        yield 'empty content' => [''];
        yield 'empty string' => ['""'];
        yield 'int' => ['0'];
        yield 'float' => ['0.1'];
        yield 'boolean' => ['false'];
        yield 'baseline with string' => ['{"baseline": "this should be a list"}'];
        yield 'baseline with int' => ['{"baseline": 0}'];
        yield 'baseline with float' => ['{"baseline": 0.0}'];
        yield 'baseline with bool' => ['{"baseline": true}'];
        yield 'baseline with array of float' => ['{"baseline": [0.0]}'];
        yield 'baseline with array of bool' => ['{"baseline": [false]}'];
        yield 'baseline with array of object' => ['{"baseline": [{}]}'];
    }
}
