<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Comparator\Support\ReflectionType as InternalReflectionType;
use Roave\ApiCompare\Comparator\Variance\TypeIsContravariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

final class TypeIsContravariantTest extends TestCase
{
    /** @dataProvider checkedTypes */
    public function testContravariance(
        ?ReflectionType $type,
        ?ReflectionType $newType,
        bool $expectedToBeContravariant
    ) : void {
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

        self::assertSame(
            $expectedToBeContravariant,
            (new TypeIsContravariant())
                ->__invoke(
                    InternalReflectionType::fromBetterReflectionTypeAndReflector($type, $reflector),
                    InternalReflectionType::fromBetterReflectionTypeAndReflector($newType, $reflector)
                )
        );
    }

    public function checkedTypes() : array
    {
        return [
            'no type to no type is contravariant with itself'                          => [
                null,
                null,
                true,
            ],
            'no type to void type is not contravariant'                                => [
                null,
                ReflectionType::createFromType('void', false),
                false,
            ],
            'void type to no type is contravariant'                                    => [
                ReflectionType::createFromType('void', false),
                null,
                true,
            ],
            'void type to scalar type is contravariant'                                => [
                ReflectionType::createFromType('void', false),
                ReflectionType::createFromType('string', false),
                true,
            ],
            'void type to class type is contravariant'                                 => [
                ReflectionType::createFromType('void', false),
                ReflectionType::createFromType('AClass', false),
                true,
            ],
            'scalar type to no type is contravariant'                                  => [
                ReflectionType::createFromType('string', false),
                null,
                true,
            ],
            'no type to scalar type is not contravariant'                              => [
                null,
                ReflectionType::createFromType('string', false),
                false,
            ],
            'class type to no type is contravariant'                                   => [
                ReflectionType::createFromType('AClass', false),
                null,
                true,
            ],
            'no type to class type is not contravariant'                               => [
                ReflectionType::createFromType('AClass', false),
                null,
                true,
            ],
            'iterable to array is not contravariant'                                   => [
                ReflectionType::createFromType('iterable', false),
                ReflectionType::createFromType('array', false),
                false,
            ],
            'array to iterable is contravariant'                                       => [
                ReflectionType::createFromType('array', false),
                ReflectionType::createFromType('iterable', false),
                true,
            ],
            'iterable to non-iterable class type is not contravariant'                 => [
                ReflectionType::createFromType('iterable', false),
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                false,
            ],
            'iterable to iterable class type is not contravariant'                     => [
                ReflectionType::createFromType('iterable', false),
                ReflectionType::createFromType('Iterator', false),
                false,
            ],
            'non-iterable class to iterable type is not contravariant'                 => [
                ReflectionType::createFromType('iterable', false),
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                false,
            ],
            'iterable class type to iterable is not contravariant'                     => [
                ReflectionType::createFromType('Iterator', false),
                ReflectionType::createFromType('iterable', false),
                false,
            ],
            'object to class type is not contravariant'                                => [
                ReflectionType::createFromType('object', false),
                ReflectionType::createFromType('AClass', false),
                false,
            ],
            'class type to object is contravariant'                                    => [
                ReflectionType::createFromType('AClass', false),
                ReflectionType::createFromType('object', false),
                true,
            ],
            'class type to scalar type is not contravariant'                           => [
                ReflectionType::createFromType('AClass', false),
                ReflectionType::createFromType('string', false),
                false,
            ],
            'scalar type to class type is not contravariant'                           => [
                ReflectionType::createFromType('string', false),
                ReflectionType::createFromType('AClass', false),
                false,
            ],
            'scalar type (string) to different scalar type (int) is not contravariant' => [
                ReflectionType::createFromType('string', false),
                ReflectionType::createFromType('int', false),
                false,
            ],
            'scalar type (int) to different scalar type (float) is not contravariant'  => [
                ReflectionType::createFromType('int', false),
                ReflectionType::createFromType('float', false),
                false,
            ],
            'object type to scalar type is not contravariant'                          => [
                ReflectionType::createFromType('object', false),
                ReflectionType::createFromType('string', false),
                false,
            ],
            'scalar type to object type is not contravariant'                          => [
                ReflectionType::createFromType('string', false),
                ReflectionType::createFromType('object', false),
                false,
            ],
            'class to superclass is contravariant'                                     => [
                ReflectionType::createFromType('BClass', false),
                ReflectionType::createFromType('AClass', false),
                true,
            ],
            'class to subclass is not contravariant'                                   => [
                ReflectionType::createFromType('BClass', false),
                ReflectionType::createFromType('CClass', false),
                false,
            ],
            'class to implemented interface is contravariant'                          => [
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                ReflectionType::createFromType('AnInterface', false),
                true,
            ],
            'class to not implemented interface is not contravariant'                  => [
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                ReflectionType::createFromType('Traversable', false),
                false,
            ],
            'interface to parent interface is contravariant'                           => [
                ReflectionType::createFromType('Iterator', false),
                ReflectionType::createFromType('Traversable', false),
                true,
            ],
            'interface to child interface is contravariant'                            => [
                ReflectionType::createFromType('Traversable', false),
                ReflectionType::createFromType('Iterator', false),
                false,
            ],
        ];
    }

    /** @dataProvider existingTypes */
    public function testContravarianceConsidersSameTypeAlwaysContravariant(?ReflectionType $type) : void
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

        self::assertTrue(
            (new TypeIsContravariant())
                ->__invoke(
                    InternalReflectionType::fromBetterReflectionTypeAndReflector($type, $reflector),
                    InternalReflectionType::fromBetterReflectionTypeAndReflector($type, $reflector)
                )
        );
    }

    public function existingTypes() : array
    {
        return array_merge(
            [[null]],
            array_merge(...array_map(
                function (string $type) : array {
                    return [
                        [ReflectionType::createFromType($type, false)],
                        [ReflectionType::createFromType($type, true)],
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
        $nullable    = InternalReflectionType::fromBetterReflectionTypeAndReflector(
            ReflectionType::createFromType($type, true),
            $reflector
        );
        $notNullable = InternalReflectionType::fromBetterReflectionTypeAndReflector(
            ReflectionType::createFromType($type, false),
            $reflector
        );

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
