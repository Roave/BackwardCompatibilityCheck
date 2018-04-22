<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Change;
use Roave\ApiCompare\Comparator\BackwardsCompatibility\InterfaceBased\InterfaceBecameTrait;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

final class InterfaceBecameTraitTest extends TestCase
{
    /**
     * @dataProvider classesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionClass $fromClass,
        ReflectionClass $toClass,
        array $expectedMessages
    ) : void {
        $changes = (new InterfaceBecameTrait())
            ->compare($fromClass, $toClass);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
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

class ConcreteToAbstract {}
abstract class AbstractToConcrete {}
class ConcreteToConcrete {}
abstract class AbstractToAbstract {}
class ConcreteToInterface {}
interface InterfaceToConcrete {}
interface InterfaceToInterface {}
interface InterfaceToAbstract {}
abstract class AbstractToInterface {}
interface InterfaceToTrait {}
trait TraitToInterface {}
trait TraitToTrait {}
PHP
            ,
            $locator
        ));
        $toReflector   = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

abstract class ConcreteToAbstract {}
class AbstractToConcrete {}
class ConcreteToConcrete {}
abstract class AbstractToAbstract {}
interface ConcreteToInterface {}
class InterfaceToConcrete {}
interface InterfaceToInterface {}
abstract class InterfaceToAbstract {}
interface AbstractToInterface {}
trait InterfaceToTrait {}
interface TraitToInterface {}
trait TraitToTrait {}
PHP
            ,
            $locator
        ));

        $classes = [
            'ConcreteToAbstract'   => [],
            'AbstractToConcrete'   => [],
            'ConcreteToConcrete'   => [],
            'AbstractToAbstract'   => [],
            'ConcreteToInterface'  => [],
            'InterfaceToConcrete'  => [],
            'InterfaceToInterface' => [],
            'InterfaceToAbstract'  => [],
            'AbstractToInterface'  => [],
            'InterfaceToTrait'     => ['[BC] CHANGED: Interface InterfaceToTrait became a trait'],
            'TraitToInterface'     => [],
            'TraitToTrait'         => [],
        ];

        return array_combine(
            array_keys($classes),
            array_map(
                function (string $className, array $errors) use ($fromReflector, $toReflector) : array {
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
