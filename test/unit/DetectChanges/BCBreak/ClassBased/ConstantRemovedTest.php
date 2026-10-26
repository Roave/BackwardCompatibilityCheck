<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\ConstantRemoved;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\StringReflectorFactory;

use function array_map;
use function iterator_to_array;

#[CoversClass(ConstantRemoved::class)]
final class ConstantRemovedTest extends TestCase
{
    /** @param string[] $expectedMessages */
    #[DataProvider('classesToBeTested')]
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
    public static function classesToBeTested(): array
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
            'final class with protected constants changing to private or being removed' => [
                (new DefaultReflector(new StringSourceLocator(
                    <<<'PHP'
                    <?php
                    final class FinalClassWithProtectedConstants {
                        protected const A = 'a';
                        protected const B = 'b';
                    }
                    PHP,
                    $locator,
                )))->reflectClass('FinalClassWithProtectedConstants'),
                (new DefaultReflector(new StringSourceLocator(
                    <<<'PHP'
                    <?php
                    final class FinalClassWithProtectedConstants {
                        private const A = 'a';
                    }
                    PHP,
                    $locator,
                )))->reflectClass('FinalClassWithProtectedConstants'),
                [],
            ],
            'final class with public constant changing to private' => [
                (new DefaultReflector(new StringSourceLocator(
                    <<<'PHP'
                    <?php
                    final class FinalClassWithPublicConstant {
                        public const A = 'a';
                    }
                    PHP,
                    $locator,
                )))->reflectClass('FinalClassWithPublicConstant'),
                (new DefaultReflector(new StringSourceLocator(
                    <<<'PHP'
                    <?php
                    final class FinalClassWithPublicConstant {
                        private const A = 'a';
                    }
                    PHP,
                    $locator,
                )))->reflectClass('FinalClassWithPublicConstant'),
                ['[BC] REMOVED: Constant FinalClassWithPublicConstant::A was removed'],
            ],
            'enum with protected constant being removed' => [
                (new StringReflectorFactory())(
                    <<<'PHP'
                    <?php
                    enum EnumWithProtectedConstant {
                        protected const A = 'a';
                    }
                    PHP,
                )->reflectClass('EnumWithProtectedConstant'),
                (new StringReflectorFactory())(
                    <<<'PHP'
                    <?php
                    enum EnumWithProtectedConstant {
                    }
                    PHP,
                )->reflectClass('EnumWithProtectedConstant'),
                [],
            ],
        ];
    }
}
