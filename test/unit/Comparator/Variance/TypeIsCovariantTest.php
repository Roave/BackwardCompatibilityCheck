<?php

declare(strict_types=1);

namespace RoaveTest\ApiCompare\Comparator\BackwardsCompatibility\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Comparator\Support\ReflectionType as InternalReflectionType;
use Roave\ApiCompare\Comparator\Variance\TypeIsCovariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

final class TypeIsCovariantTest extends TestCase
{
    /** @dataProvider checkedTypes */
    public function testCovariance(
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
            (new TypeIsCovariant())
                ->__invoke(
                    InternalReflectionType::fromBetterReflectionTypeAndReflector($type, $reflector),
                    InternalReflectionType::fromBetterReflectionTypeAndReflector($newType, $reflector)
                )
        );
    }

    public function checkedTypes() : array
    {
        return [
            'no type to void type is covariant'                    => [
                null,
                ReflectionType::createFromType('void', false),
                true,
            ],
            'void type to no type is not covariant'                => [
                ReflectionType::createFromType('void', false),
                null,
                false,
            ],
            'void type to scalar type is not covariant'            => [
                ReflectionType::createFromType('void', false),
                ReflectionType::createFromType('string', false),
                false,
            ],
            'void type to class type is covariant'                 => [
                ReflectionType::createFromType('void', false),
                ReflectionType::createFromType('AClass', false),
                false,
            ],
            'scalar type to no type is not covariant'              => [
                ReflectionType::createFromType('string', false),
                null,
                false,
            ],
            'no type to scalar type is covariant'                  => [
                null,
                ReflectionType::createFromType('string', false),
                true,
            ],
            'class type to no type is not covariant'               => [
                ReflectionType::createFromType('AClass', false),
                null,
                false,
            ],
            'no type to class type is not contravariant'           => [
                ReflectionType::createFromType('AClass', false),
                null,
                false,
            ],
            'iterable to non-iterable class type is not covariant' => [
                ReflectionType::createFromType('iterable', false),
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                false,
            ],
            'iterable to iterable class type is covariant'         => [
                ReflectionType::createFromType('iterable', false),
                ReflectionType::createFromType('Iterator', false),
                true,
            ],
            'non-iterable class to iterable type is not covariant' => [
                ReflectionType::createFromType('iterable', false),
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                false,
            ],
            'iterable class type to iterable is not covariant'     => [
                ReflectionType::createFromType('Iterator', false),
                ReflectionType::createFromType('iterable', false),
                false,
            ],
            'object to class type is covariant'                    => [
                ReflectionType::createFromType('object', false),
                ReflectionType::createFromType('AClass', false),
                true,
            ],
            'class type to object is not covariant'                => [
                ReflectionType::createFromType('AClass', false),
                ReflectionType::createFromType('object', false),
                false,
            ],

            'class type to scalar type is not covariant'                           => [
                ReflectionType::createFromType('AClass', false),
                ReflectionType::createFromType('string', false),
                false,
            ],
            'scalar type to class type is not covariant'                           => [
                ReflectionType::createFromType('string', false),
                ReflectionType::createFromType('AClass', false),
                false,
            ],
            'scalar type (string) to different scalar type (int) is not covariant' => [
                ReflectionType::createFromType('string', false),
                ReflectionType::createFromType('int', false),
                false,
            ],
            'scalar type (int) to different scalar type (float) is not covariant'  => [
                ReflectionType::createFromType('int', false),
                ReflectionType::createFromType('float', false),
                false,
            ],
            'object type to scalar type is not contravariant'                      => [
                ReflectionType::createFromType('object', false),
                ReflectionType::createFromType('string', false),
                false,
            ],
            'scalar type to object type is not covariant'                          => [
                ReflectionType::createFromType('string', false),
                ReflectionType::createFromType('object', false),
                false,
            ],
            'class to superclass is not covariant'                                 => [
                ReflectionType::createFromType('BClass', false),
                ReflectionType::createFromType('AClass', false),
                false,
            ],
            'class to subclass is covariant'                                       => [
                ReflectionType::createFromType('BClass', false),
                ReflectionType::createFromType('CClass', false),
                true,
            ],
            'class to implemented interface is not covariant'                      => [
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                ReflectionType::createFromType('AnInterface', false),
                false,
            ],
            'interface to implementing class is covariant'                         => [
                ReflectionType::createFromType('AnInterface', false),
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                true,
            ],
            'class to not implemented interface is not covariant'                  => [
                ReflectionType::createFromType('AnotherClassWithMultipleInterfaces', false),
                ReflectionType::createFromType('Traversable', false),
                false,
            ],
            'interface to parent interface is not covariant'                       => [
                ReflectionType::createFromType('Iterator', false),
                ReflectionType::createFromType('Traversable', false),
                false,
            ],
            'interface to child interface is covariant'                            => [
                ReflectionType::createFromType('Traversable', false),
                ReflectionType::createFromType('Iterator', false),
                true,
            ],
        ];
    }

    /** @dataProvider existingTypes */
    public function testCovarianceConsidersSameTypeAlwaysCovariant(?ReflectionType $type) : void
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
            (new TypeIsCovariant())
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
    public function testCovarianceConsidersNullability(string $type) : void
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

        $nullable    = InternalReflectionType::fromBetterReflectionTypeAndReflector(
            ReflectionType::createFromType($type, true),
            $reflector
        );
        $notNullable = InternalReflectionType::fromBetterReflectionTypeAndReflector(
            ReflectionType::createFromType($type, false),
            $reflector
        );

        $isCovariant = new TypeIsCovariant();

        self::assertTrue($isCovariant->__invoke($nullable, $notNullable));
        self::assertFalse($isCovariant->__invoke($notNullable, $nullable));
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
