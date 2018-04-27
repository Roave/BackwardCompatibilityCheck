<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_map;
use function array_merge;

final class TypeIsCovariantTest extends TestCase
{
    /** @dataProvider checkedTypes */
    public function testCovariance(
        ?ReflectionType $type,
        ?ReflectionType $newType,
        bool $expectedToBeContravariant
    ) : void {
        self::assertSame(
            $expectedToBeContravariant,
            (new TypeIsCovariant())
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
            'no type to void type is covariant'                    => [
                null,
                ReflectionType::createFromTypeAndReflector('void', false, $reflector),
                true,
            ],
            'void type to no type is not covariant'                => [
                ReflectionType::createFromTypeAndReflector('void', false, $reflector),
                null,
                false,
            ],
            'void type to scalar type is not covariant'            => [
                ReflectionType::createFromTypeAndReflector('void', false, $reflector),
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                false,
            ],
            'void type to class type is covariant'                 => [
                ReflectionType::createFromTypeAndReflector('void', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                false,
            ],
            'scalar type to no type is not covariant'              => [
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                null,
                false,
            ],
            'no type to scalar type is covariant'                  => [
                null,
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                true,
            ],
            'class type to no type is not covariant'               => [
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                null,
                false,
            ],
            'no type to class type is not contravariant'           => [
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                null,
                false,
            ],
            'iterable to array is covariant' => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('array', false, $reflector),
                true,
            ],
            'iterable to scalar is not covariant' => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('int', false, $reflector),
                false,
            ],
            'scalar to iterable is not covariant' => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('int', false, $reflector),
                false,
            ],
            'array to iterable is not covariant'         => [
                ReflectionType::createFromTypeAndReflector('array', false, $reflector),
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                false,
            ],
            'iterable to non-iterable class type is not covariant' => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                false,
            ],
            'iterable to iterable class type is covariant'         => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('Iterator', false, $reflector),
                true,
            ],
            'non-iterable class to iterable type is not covariant' => [
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                false,
            ],
            'iterable class type to iterable is not covariant'     => [
                ReflectionType::createFromTypeAndReflector('Iterator', false, $reflector),
                ReflectionType::createFromTypeAndReflector('iterable', false, $reflector),
                false,
            ],
            'object to class type is covariant'                    => [
                ReflectionType::createFromTypeAndReflector('object', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                true,
            ],
            'class type to object is not covariant'                => [
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                ReflectionType::createFromTypeAndReflector('object', false, $reflector),
                false,
            ],

            'class type to scalar type is not covariant'                           => [
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                false,
            ],
            'scalar type to class type is not covariant'                           => [
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                false,
            ],
            'scalar type (string) to different scalar type (int) is not covariant' => [
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                ReflectionType::createFromTypeAndReflector('int', false, $reflector),
                false,
            ],
            'scalar type (int) to different scalar type (float) is not covariant'  => [
                ReflectionType::createFromTypeAndReflector('int', false, $reflector),
                ReflectionType::createFromTypeAndReflector('float', false, $reflector),
                false,
            ],
            'object type to scalar type is not contravariant'                      => [
                ReflectionType::createFromTypeAndReflector('object', false, $reflector),
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                false,
            ],
            'scalar type to object type is not covariant'                          => [
                ReflectionType::createFromTypeAndReflector('string', false, $reflector),
                ReflectionType::createFromTypeAndReflector('object', false, $reflector),
                false,
            ],
            'class to superclass is not covariant'                                 => [
                ReflectionType::createFromTypeAndReflector('BClass', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AClass', false, $reflector),
                false,
            ],
            'class to subclass is covariant'                                       => [
                ReflectionType::createFromTypeAndReflector('BClass', false, $reflector),
                ReflectionType::createFromTypeAndReflector('CClass', false, $reflector),
                true,
            ],
            'class to implemented interface is not covariant'                      => [
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AnInterface', false, $reflector),
                false,
            ],
            'interface to implementing class is covariant'                         => [
                ReflectionType::createFromTypeAndReflector('AnInterface', false, $reflector),
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                true,
            ],
            'class to not implemented interface is not covariant'                  => [
                ReflectionType::createFromTypeAndReflector('AnotherClassWithMultipleInterfaces', false, $reflector),
                ReflectionType::createFromTypeAndReflector('Traversable', false, $reflector),
                false,
            ],
            'interface to parent interface is not covariant'                       => [
                ReflectionType::createFromTypeAndReflector('Iterator', false, $reflector),
                ReflectionType::createFromTypeAndReflector('Traversable', false, $reflector),
                false,
            ],
            'interface to child interface is covariant'                            => [
                ReflectionType::createFromTypeAndReflector('Traversable', false, $reflector),
                ReflectionType::createFromTypeAndReflector('Iterator', false, $reflector),
                true,
            ],
        ];
    }

    /** @dataProvider existingTypes */
    public function testCovarianceConsidersSameTypeAlwaysCovariant(?ReflectionType $type) : void
    {
        self::assertTrue(
            (new TypeIsCovariant())
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
    public function testCovarianceConsidersNullability(string $type) : void
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
