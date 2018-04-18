<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Comparator\Variance\TypeIsContravariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function array_merge;

final class TypeIsContravariantTest extends TestCase
{
    /** @dataProvider checkedTypes */
    public function testContravariance(
        ?ReflectionType $type,
        ?ReflectionType $newType,
        bool $expectedToBeContravariant
    ) : void {
        self::assertSame(
            $expectedToBeContravariant,
            (new TypeIsContravariant())
                ->__invoke($type, $newType)
        );
    }

    /** @return (null|bool|ReflectionType)[][] */
    public function checkedTypes() : array
    {
        $reflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
interface Iterator extends Traversable {}
interface AnInterface {}
interface AnotherInterface {}
class AnotherClass implements AnInterface {}
class AnotherClassWithMultipleInterfaces implements AnInterface, AnotherInterface {}
class AClass {}
class BClass extends AClass {}
class CClass extends BClass {}
PHP
            ,
            (new BetterReflection())->astLocator()
        ));
        
        return [
            'no type to no type is contravariant with itself'                          => [
                null,
                null,
                true,
            ],
            'no type to void type is not contravariant'                                => [
                null,
                ReflectionType::createFromTypeAndReflector('void', false, $reflector),
                false,
            ],
            'void type to no type is contravariant'                                    => [
                ReflectionType::createFromTypeAndReflector('void', false, $reflector),
                null,
                true,
            ],
            'void type to scalar type is contravariant'                                => [
                ReflectionType::createFromTypeAndReflector('void', false, $reflector),
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                true,
            ],
            'void type to class type is contravariant'                                 => [
                ReflectionType::createFromTypeAndReflector('void', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                true,
            ],
            'scalar type to no type is contravariant'                                  => [
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                null,
                true,
            ],
            'no type to scalar type is not contravariant'                              => [
                null,
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                false,
            ],
            'class type to no type is contravariant'                                   => [
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                null,
                true,
            ],
            'no type to class type is not contravariant'                               => [
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                null,
                true,
            ],
            'iterable to array is not contravariant'                 => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('array', false, $reflector),
                false,
            ],
            'array to iterable is contravariant'                 => [
                ReflectionType::createFromTypeAndReflector('array', false, $reflector),
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                true,
            ],
            'iterable to non-iterable class type is not contravariant'                 => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                false,
            ],
            'iterable to iterable class type is not contravariant'                         => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('Iterator', false, $reflector),
                false,
            ],
            'non-iterable class to iterable type is not contravariant'                 => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                false,
            ],
            'iterable class type to iterable is not contravariant'                     => [
                ReflectionType::createFromTypeAndReflector('Iterator', false, $reflector),
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                false,
            ],
            'object to class type is not contravariant'                                => [
                ReflectionType::createFromTypeAndReflector('object', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                false,
            ],
            'class type to object is contravariant'                                    => [
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                ReflectionType::createFromTypeAndReflector('object', false, $reflector),
                true,
            ],
            'class type to scalar type is not contravariant'                           => [
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                false,
            ],
            'scalar type to class type is not contravariant'                           => [
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                false,
            ],
            'scalar type (string) to different scalar type (int) is not contravariant' => [
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                ReflectionType::createFromTypeAndReflector('int', false, $reflector),
                false,
            ],
            'scalar type (int) to different scalar type (float) is not contravariant'  => [
                ReflectionType::createFromTypeAndReflector('int', false, $reflector),
                ReflectionType::createFromTypeAndReflector('float', false, $reflector),
                false,
            ],
            'object type to scalar type is not contravariant'                          => [
                ReflectionType::createFromTypeAndReflector('object', false, $reflector),
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                false,
            ],
            'scalar type to object type is not contravariant'                          => [
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                ReflectionType::createFromTypeAndReflector('object', false, $reflector),
                false,
            ],
            'class to superclass is contravariant'                                     => [
                ReflectionType::createFromTypeAndReflector('BClass', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                true,
            ],
            'class to subclass is not contravariant'                                   => [
                ReflectionType::createFromTypeAndReflector('BClass', false, $reflector),
                ReflectionType::createFromTypeAndReflector('CClass', false, $reflector),
                false,
            ],
            'class to implemented interface is contravariant'                          => [
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AnInterface', false, $reflector),
                true,
            ],
            'class to not implemented interface is not contravariant'                  => [
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                ReflectionType::createFromTypeAndReflector('Traversable', false, $reflector),
                false,
            ],
            'interface to parent interface is contravariant'                           => [
                ReflectionType::createFromTypeAndReflector('Iterator', false, $reflector),
                ReflectionType::createFromTypeAndReflector('Traversable', false, $reflector),
                true,
            ],
            'interface to child interface is contravariant'                            => [
                ReflectionType::createFromTypeAndReflector('Traversable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('Iterator', false, $reflector),
                false,
            ],
        ];
    }

    /** @dataProvider existingTypes */
    public function testContravarianceConsidersSameTypeAlwaysContravariant(?ReflectionType $type) : void
    {
        self::assertTrue(
            (new TypeIsContravariant())
                ->__invoke($type, $type)
        );
    }

    /** @return (null|ReflectionType)[][] */
    public function existingTypes() : array
    {
        $reflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
class AClass {}
PHP
            ,
            (new BetterReflection())->astLocator()
        ));

        return array_merge(
            [[null]],
            array_merge(...array_map(
                function (string $type) use ($reflector) : array {
                    return [
                        [ReflectionType::createFromTypeAndReflector($type, false, $reflector)],
                        [ReflectionType::createFromTypeAndReflector($type, true, $reflector)],
                    ];
                },
                [
                    'int',
                    'string',
                    'float',
                    'bool',
                    'array',
                    'iterable',
                    'callable',
                    'Traversable',
                    'AClass',
                ]
            ))
        );
    }

    /** @dataProvider existingNullableTypeStrings */
    public function testContravarianceConsidersNullability(string $type) : void
    {
        $reflector   = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
class AClass {}
PHP
            ,
            (new BetterReflection())->astLocator()
        ));
        $nullable    = ReflectionType::createFromTypeAndReflector($type, true, $reflector);
        $notNullable = ReflectionType::createFromTypeAndReflector($type, false, $reflector);

        $isContravariant = new TypeIsContravariant();

        self::assertFalse($isContravariant->__invoke($nullable, $notNullable));
        self::assertTrue($isContravariant->__invoke($notNullable, $nullable));
    }

    /** @return string[][] */
    public function existingNullableTypeStrings() : array
    {
        return [
            ['int'],
            ['string'],
            ['float'],
            ['bool'],
            ['array'],
            ['iterable'],
            ['callable'],
            ['Traversable'],
            ['AClass'],
        ];
    }
}
