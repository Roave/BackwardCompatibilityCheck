<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ConstantRemoved;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

use function array_map;
use function iterator_to_array;

final class ConstantRemovedTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider classesToBeTested
     */
    public function testDiffs(
        ReflectionClass $fromClass,
        ReflectionClass $toClass,
        array $expectedMessages,
    ): void {
        $changes = (new ConstantRemoved())($fromClass, $toClass);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change): string {
                return $change->__toString();
            }, iterator_to_array($changes)),
        );
    }

    /**
     * @return array<string, array<int, ReflectionClass|array<int, string>>>
     * @psalm-return array<string, array{0: ReflectionClass, 1: ReflectionClass, 2: list<string>}>
     */
    public function classesToBeTested(): array
    {
        $locator = (new BetterReflection())->astLocator();

        return [
            'RoaveTestAsset\\ClassWithConstantsBeingRemoved' => [
                (new DefaultReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/old/ClassWithConstantsBeingRemoved.php',
                    $locator,
                )))->reflectClass('RoaveTestAsset\\ClassWithConstantsBeingRemoved'),
                (new DefaultReflector(new SingleFileSourceLocator(
                    __DIR__ . '/../../../../asset/api/new/ClassWithConstantsBeingRemoved.php',
                    $locator,
                )))->reflectClass('RoaveTestAsset\\ClassWithConstantsBeingRemoved'),
                [
                    '[BC] REMOVED: Constant RoaveTestAsset\ClassWithConstantsBeingRemoved::removedPublicConstant was removed',
                    '[BC] REMOVED: Constant RoaveTestAsset\ClassWithConstantsBeingRemoved::nameCaseChangePublicConstant was removed',
                    '[BC] REMOVED: Constant RoaveTestAsset\ClassWithConstantsBeingRemoved::removedProtectedConstant was removed',
                    '[BC] REMOVED: Constant RoaveTestAsset\ClassWithConstantsBeingRemoved::nameCaseChangeProtectedConstant was removed',
                ],
            ],
        ];
    }
}
