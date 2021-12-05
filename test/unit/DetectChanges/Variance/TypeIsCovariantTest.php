<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\Variance;

use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPUnit\Framework\TestCase;
use Psl\Type;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionIntersectionType;
use Roave\BetterReflection\Reflection\ReflectionNamedType;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflection\ReflectionUnionType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_map;
use function array_merge;

/** @covers \Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsCovariant */
final class TypeIsCovariantTest extends TestCase
{
    /**
     * @dataProvider checkedTypes
     */
    public function testCovariance(
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $type,
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $newType,
        bool $expectedToBeContravariant
    ): void {
        self::assertSame(
            $expectedToBeContravariant,
            (new TypeIsCovariant())($type, $newType)
        );
    }

    /**
     * @return array<string, array{
     *     0: ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null,
     *     1: ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null,
     *     2: bool
     * }>
     */
    public function checkedTypes(): array
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
interface Iterator extends Traversable {}
interface AnInterface {}
interface AnotherInterface {}
interface A {}
interface B {}
interface C {}
interface D {}
class AnotherClass implements AnInterface {}
class AnotherClassWithMultipleInterfaces implements AnInterface, AnotherInterface {}
class AClass {}
class BClass extends AClass {}
class CClass extends BClass {}
final class OwnerPropertyContainer { private $owner; }
PHP
            ,
            (new BetterReflection())->astLocator()
        ));

        $owner = Type\object(ReflectionProperty::class)
            ->coerce(
                $reflector->reflectClass('OwnerPropertyContainer')
                    ->getProperty('owner')
            );

        $types = [
            'no type to void type is covariant'                    => [
                null,
                new Identifier('void'),
                true,
            ],
            'scalar type to void type is not covariant'                    => [
                new Identifier('string'),
                new Identifier('void'),
                false,
            ],
            'void type to no type is not covariant'                => [
                new Identifier('void'),
                null,
                false,
            ],
            'void type to scalar type is not covariant'            => [
                new Identifier('void'),
                new Identifier('string'),
                false,
            ],
            'void type to class type is covariant'                 => [
                new Identifier('void'),
                new Identifier('AClass'),
                false,
            ],
            'mixed to no type is not covariant'                => [
                new Identifier('mixed'),
                null,
                false,
            ],
            'no type to mixed is covariant'                => [
                null,
                new Identifier('mixed'),
                true,
            ],

            'never to no type is not covariant'                => [
                new Identifier('never'),
                null,
                false,
            ],
            'no type to never is covariant'                => [
                null,
                new Identifier('never'),
                true,
            ],
            'scalar type to never is covariant'                => [
                new Identifier('int'),
                new Identifier('never'),
                true,
            ],
            'scalar type to no type is not covariant'              => [
                new Identifier('string'),
                null,
                false,
            ],
            'no type to scalar type is covariant'                  => [
                null,
                new Identifier('string'),
                true,
            ],
            'class type to no type is not covariant'               => [
                new Identifier('AClass'),
                null,
                false,
            ],
            'no type to class type is not contravariant'           => [
                new Identifier('AClass'),
                null,
                false,
            ],
            'iterable to array is covariant' => [
                new Identifier('iterable'),
                new Identifier('array'),
                true,
            ],
            'iterable to scalar is not covariant' => [
                new Identifier('iterable'),
                new Identifier('int'),
                false,
            ],
            'scalar to iterable is not covariant' => [
                new Identifier('iterable'),
                new Identifier('int'),
                false,
            ],
            'array to iterable is not covariant'         => [
                new Identifier('array'),
                new Identifier('iterable'),
                false,
            ],
            'iterable to non-iterable class type is not covariant' => [
                new Identifier('iterable'),
                new Identifier('AnotherClassWithMultipleInterfaces'),
                false,
            ],
            'iterable to iterable class type is covariant'         => [
                new Identifier('iterable'),
                new Identifier('Iterator'),
                true,
            ],
            'non-iterable class to iterable type is not covariant' => [
                new Identifier('iterable'),
                new Identifier('AnotherClassWithMultipleInterfaces'),
                false,
            ],
            'iterable class type to iterable is not covariant'     => [
                new Identifier('Iterator'),
                new Identifier('iterable'),
                false,
            ],
            'object to class type is covariant'                    => [
                new Identifier('object'),
                new Identifier('AClass'),
                true,
            ],
            'class type to object is not covariant'                => [
                new Identifier('AClass'),
                new Identifier('object'),
                false,
            ],
            'mixed to object is covariant'                => [
                new Identifier('mixed'),
                new Identifier('object'),
                true,
            ],
            'object to mixed is not covariant'                => [
                new Identifier('object'),
                new Identifier('mixed'),
                false,
            ],

            'class type to scalar type is not covariant'                           => [
                new Identifier('AClass'),
                new Identifier('string'),
                false,
            ],
            'scalar type to class type is not covariant'                           => [
                new Identifier('string'),
                new Identifier('AClass'),
                false,
            ],
            'scalar type (string) to different scalar type (int) is not covariant' => [
                new Identifier('string'),
                new Identifier('int'),
                false,
            ],
            'scalar type (int) to different scalar type (float) is not covariant'  => [
                new Identifier('int'),
                new Identifier('float'),
                false,
            ],
            'object type to scalar type is not contravariant'                      => [
                new Identifier('object'),
                new Identifier('string'),
                false,
            ],
            'scalar type to object type is not covariant'                          => [
                new Identifier('string'),
                new Identifier('object'),
                false,
            ],
            'class to superclass is not covariant'                                 => [
                new Identifier('BClass'),
                new Identifier('AClass'),
                false,
            ],
            'class to subclass is covariant'                                       => [
                new Identifier('BClass'),
                new Identifier('CClass'),
                true,
            ],
            'class to implemented interface is not covariant'                      => [
                new Identifier('AnotherClassWithMultipleInterfaces'),
                new Identifier('AnInterface'),
                false,
            ],
            'interface to implementing class is covariant'                         => [
                new Identifier('AnInterface'),
                new Identifier('AnotherClassWithMultipleInterfaces'),
                true,
            ],
            'class to not implemented interface is not covariant'                  => [
                new Identifier('AnotherClassWithMultipleInterfaces'),
                new Identifier('Traversable'),
                false,
            ],
            'interface to parent interface is not covariant'                       => [
                new Identifier('Iterator'),
                new Identifier('Traversable'),
                false,
            ],
            'interface to child interface is covariant'                            => [
                new Identifier('Traversable'),
                new Identifier('Iterator'),
                true,
            ],

            'scalar type to union type is not covariant'                            => [
                new Identifier('int'),
                new UnionType([new Identifier('int'), new Identifier('string')]),
                false,
            ],
            'union type to scalar is covariant'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new Identifier('int'),
                true,
            ],
            'same union type is covariant'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new UnionType([new Identifier('int'), new Identifier('string')]),
                true,
            ],
            'same union type (in reverse order) is covariant'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new UnionType([new Identifier('string'), new Identifier('int')]),
                true,
            ],
            'incompatible union types are not covariant'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new UnionType([new Identifier('float'), new Identifier('bool')]),
                false,
            ],
            'union type to wider union type is not covariant - https://3v4l.org/Tudl8#v8.1rc3'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new UnionType([new Identifier('int'), new Identifier('string'), new Identifier('float')]),
                false,
            ],
            'union type to narrower union type is covariant - https://3v4l.org/RB2fC#v8.1rc3'                            => [
                new UnionType([new Identifier('int'), new Identifier('string'), new Identifier('float')]),
                new UnionType([new Identifier('int'), new Identifier('string')]),
                true,
            ],

            'object type to intersection type is covariant'                            => [
                new Identifier('A'),
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                true,
            ],
            'intersection type to object type is not covariant'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new Identifier('A'),
                false,
            ],
            'same intersection type is covariant'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                true,
            ],
            'same intersection type (in reverse order) is covariant'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new IntersectionType([new Identifier('B'), new Identifier('A')]),
                true,
            ],
            'incompatible intersection types are not covariant'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new IntersectionType([new Identifier('C'), new Identifier('D')]),
                false,
            ],
            'intersection type to stricter intersection type is covariant - https://3v4l.org/NoV52#v8.1rc3'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new IntersectionType([new Identifier('A'), new Identifier('B'), new Identifier('C')]),
                true,
            ],
            'intersection type to less specific intersection type is not covariant - https://3v4l.org/8FSuK#v8.1rc3'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B'), new Identifier('C')]),
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                false,
            ],
        ];

        return array_map(
            static fn (array $types): array => [
                $types[0] === null
                    ? null
                    : self::identifierType($reflector, $owner, $types[0]),
                $types[1] === null
                    ? null
                    : self::identifierType($reflector, $owner, $types[1]),
                $types[2],
            ],
            $types
        );
    }

    /**
     * @dataProvider existingTypes
     */
    public function testCovarianceConsidersSameTypeAlwaysCovariant(
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $type
    ): void {
        self::assertTrue(
            (new TypeIsCovariant())($type, $type)
        );
    }

    /**
     * @return list<array{ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null}>
     */
    public function existingTypes(): array
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
class AClass {}
final class OwnerPropertyContainer { private $owner; }
PHP
            ,
            (new BetterReflection())->astLocator()
        ));

        $owner = Type\object(ReflectionProperty::class)
            ->coerce(
                $reflector->reflectClass('OwnerPropertyContainer')
                    ->getProperty('owner')
            );

        return array_merge(
            [[null]],
            array_merge(...array_map(
                static fn (string $type): array => [
                    [self::identifierType($reflector, $owner, new Identifier($type))],
                    [self::identifierType($reflector, $owner, new NullableType(new Identifier($type)))],
                ],
                [
                    'mixed',
                    'never',
                    'void',
                    'int',
                    'string',
                    'float',
                    'bool',
                    'array',
                    'iterable',
                    'callable',
                    'object',
                    'Traversable',
                    'AClass',
                ]
            ))
        );
    }

    /**
     * @dataProvider existingNullableTypeStrings
     */
    public function testCovarianceConsidersNullability(string $type): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
class AClass {}
final class OwnerPropertyContainer { private $owner; }
PHP
            ,
            (new BetterReflection())->astLocator()
        ));

        $owner = Type\object(ReflectionProperty::class)
            ->coerce(
                $reflector->reflectClass('OwnerPropertyContainer')
                    ->getProperty('owner')
            );

        $nullable    = self::identifierType($reflector, $owner, new NullableType(new Identifier($type)));
        $notNullable = self::identifierType($reflector, $owner, new Identifier($type));

        $isCovariant = new TypeIsCovariant();

        self::assertTrue($isCovariant($nullable, $notNullable));
        self::assertFalse($isCovariant($notNullable, $nullable));
    }

    /** @return string[][] */
    public function existingNullableTypeStrings(): array
    {
        return [
            ['int'],
            ['string'],
            ['float'],
            ['bool'],
            ['array'],
            ['iterable'],
            ['callable'],
            ['object'],
            ['Traversable'],
            ['AClass'],
        ];
    }

    private static function identifierType(
        Reflector $reflector,
        ReflectionProperty $owner,
        Identifier|NullableType|UnionType|IntersectionType $identifier
    ): ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType {
        return ReflectionType::createFromNode($reflector, $owner, $identifier);
    }
}
