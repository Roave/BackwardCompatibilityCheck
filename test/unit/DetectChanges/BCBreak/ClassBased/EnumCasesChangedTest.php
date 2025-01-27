<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\EnumCasesChanged;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use stdClass;

use function array_map;
use function iterator_to_array;

#[CoversClass(EnumCasesChanged::class)]
final class EnumCasesChangedTest extends TestCase
{
    /** @param string[] $expectedMessages */
    #[DataProvider('enumsToBeTested')]
    public function testDiffs(
        ReflectionClass $fromEnum,
        ReflectionClass $toEnum,
        array $expectedMessages,
    ): void {
        $changes = (new EnumCasesChanged())($fromEnum, $toEnum);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    public function testReturnsClassBecameEnumError(): void
    {
        $changes = (new EnumCasesChanged())(
            ReflectionClass::createFromName(stdClass::class),
            ReflectionClass::createFromName(DummyEnum::class),
        );

        $this->assertEquals(
            [Change::changed('class stdClass became enum')],
            iterator_to_array($changes),
        );
    }

    public function testReturnsEnumBecameClassError(): void
    {
        $changes = (new EnumCasesChanged())(
            ReflectionClass::createFromName(DummyEnum::class),
            ReflectionClass::createFromName(stdClass::class),
        );

        $this->assertEquals(
            [Change::changed('enum RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\DummyEnum became class')],
            iterator_to_array($changes),
        );
    }

    /**
     * @return array<string, array<int, ReflectionClass|array<int, string>>>
     * @psalm-return array<string, array{0: ReflectionClass, 1: ReflectionClass, 2: list<string>}>
     */
    public static function enumsToBeTested(): array
    {
        $locator = (new BetterReflection())->astLocator();

        return [
            'RoaveTestAsset\\EnumWithCasesBeingChanged' => [
                (new DefaultReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/old/EnumWithCasesBeingChanged.php',
                    $locator,
                )))->reflectClass('RoaveTestAsset\EnumWithCasesBeingChanged'),
                (new DefaultReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/new/EnumWithCasesBeingChanged.php',
                    $locator,
                )))->reflectClass('RoaveTestAsset\EnumWithCasesBeingChanged'),
                [
                    '[BC] REMOVED: Case RoaveTestAsset\EnumWithCasesBeingChanged::August was removed',
                    '[BC] REMOVED: Case RoaveTestAsset\EnumWithCasesBeingChanged::december was removed',
                    '[BC] ADDED: Case RoaveTestAsset\EnumWithCasesBeingChanged::January was added',
                    '[BC] ADDED: Case RoaveTestAsset\EnumWithCasesBeingChanged::February was added',
                    '[BC] ADDED: Case RoaveTestAsset\EnumWithCasesBeingChanged::December was added',
                    '[BC] CHANGED: Case RoaveTestAsset\EnumWithCasesBeingChanged::March was marked "@internal"',
                    '[BC] CHANGED: Case RoaveTestAsset\EnumWithCasesBeingChanged::UnknownMonth had "@internal" removed',
                ],
            ],
        ];
    }
}
