<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\TraitBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBecameClass;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_keys;
use function array_map;
use function iterator_to_array;
use function Safe\array_combine;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\TraitBased\TraitBecameClass
 */
final class TraitBecameClassTest extends TestCase
{
    /**
     * @param string[] $expectedMessages
     *
     * @dataProvider classesToBeTested
     */
    public function testDiffs(
        ReflectionClass $fromClass,
        ReflectionClass $toClass,
        array $expectedMessages
    ) : void {
        $changes = (new TraitBecameClass())
            ->__invoke($fromClass, $toClass);

        self::assertSame(
            $expectedMessages,
            array_map(static function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionClass)[][] */
    public function classesToBeTested() : array
    {
        $locator       = (new BetterReflection())->astLocator();
        $fromReflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

trait TraitToClass {}
trait TraitToInterface {}
class ClassToTrait {}
trait TraitToTrait {}
class ClassToClass {}
interface InterfaceToTrait {}
interface InterfaceToInterface {}
PHP
            ,
            $locator
        ));
        $toReflector   = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

class TraitToClass {}
interface TraitToInterface {}
trait ClassToTrait {}
trait TraitToTrait {}
class ClassToClass {}
trait InterfaceToTrait {}
interface InterfaceToInterface {}
PHP
            ,
            $locator
        ));

        $classes = [
            'TraitToClass'         => ['[BC] CHANGED: Trait TraitToClass became a class'],
            'TraitToInterface'     => [],
            'ClassToTrait'         => [],
            'TraitToTrait'         => [],
            'ClassToClass'         => [],
            'InterfaceToTrait'     => [],
            'InterfaceToInterface' => [],
        ];

        return array_combine(
            array_keys($classes),
            array_map(
                static function (string $className, array $errors) use ($fromReflector, $toReflector) : array {
                    return [
                        $fromReflector->reflect($className),
                        $toReflector->reflect($className),
                        $errors,
                    ];
                },
                array_keys($classes),
                $classes
            )
        );
    }
}
