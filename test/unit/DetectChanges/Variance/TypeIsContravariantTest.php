<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\Variance;

use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use PHPUnit\Framework\TestCase;
use Psl\Type;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
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

/** @covers \Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant */
final class TypeIsContravariantTest extends TestCase
{
    /** @dataProvider checkedTypes */
    public function testContravariance(
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $type,
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $newType,
        bool $expectedToBeContravariant,
    ): void {
        self::assertSame(
            $expectedToBeContravariant,
            (new TypeIsContravariant())($type, $newType),
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
            (new BetterReflection())->astLocator(),
        ));

        $owner = Type\instance_of(ReflectionProperty::class)
            ->coerce(
                $reflector->reflectClass('OwnerPropertyContainer')
                    ->getProperty('owner'),
            );

        $types = [
            'no type to no type is contravariant with itself'                          => [
                null,
                null,
                true,
            ],
            'nullable type to null is not contravariant - this does not occur in the real world, but is an important base invariant'     => [
                new NullableType(new Identifier('AnInterface')),
                new Identifier('null'),
                false,
            ],
            'null to nullable type is covariant - this does not occur in the real world, but is an important base invariant' => [
                new Identifier('null'),
                new NullableType(new Identifier('AnInterface')),
                true,
            ],
            'no type to void type is not contravariant'                                => [
                null,
                new Identifier('void'),
                false,
            ],
            'scalar type to void type is not covariant - note that void is enforced, and has no substitutes in PHP'                    => [
                new Identifier('string'),
                new Identifier('void'),
                false,
            ],
            'void type to no type is contravariant'                                    => [
                new Identifier('void'),
                null,
                true,
            ],
            'void type to scalar type is contravariant'                                => [
                new Identifier('void'),
                new Identifier('string'),
                true,
            ],
            'void type to class type is contravariant'                                 => [
                new Identifier('void'),
                new Identifier('AClass'),
                true,
            ],
            'mixed to no type is contravariant'                => [
                new Identifier('mixed'),
                null,
                true,
            ],
            'no type to mixed is contravariant'                => [
                null,
                new Identifier('mixed'),
                true,
            ],

            'never to no type is not contravariant - note that never is enforced, and has no substitutes in PHP'                => [
                new Identifier('never'),
                null,
                false,
            ],
            'no type to never is not contravariant - note that never is enforced, and has no substitutes in PHP'                => [
                null,
                new Identifier('never'),
                false,
            ],
            'scalar type to never is not contravariant - note that never is enforced, and has no substitutes in PHP'                => [
                new Identifier('int'),
                new Identifier('never'),
                false,
            ],
            'scalar type to no type is contravariant'                                  => [
                new Identifier('string'),
                null,
                true,
            ],
            'no type to scalar type is not contravariant'                              => [
                null,
                new Identifier('string'),
                false,
            ],
            'class type to no type is contravariant'                                   => [
                new Identifier('AClass'),
                null,
                true,
            ],
            'no type to class type is not contravariant'                               => [
                new Identifier('AClass'),
                null,
                true,
            ],
            'iterable to array is not contravariant'                 => [
                new Identifier('iterable'),
                new Identifier('array'),
                false,
            ],
            'array to iterable is contravariant'                 => [
                new Identifier('array'),
                new Identifier('iterable'),
                true,
            ],
            'iterable to non-iterable class type is not contravariant'                 => [
                new Identifier('iterable'),
                new Identifier('AnotherClassWithMultipleInterfaces'),
                false,
            ],
            'iterable to iterable class type is not contravariant'                         => [
                new Identifier('iterable'),
                new Identifier('Iterator'),
                false,
            ],
            'non-iterable class to iterable type is not contravariant'                 => [
                new Identifier('iterable'),
                new Identifier('AnotherClassWithMultipleInterfaces'),
                false,
            ],
            'iterable class type to iterable is not contravariant'                     => [
                new Identifier('Iterator'),
                new Identifier('iterable'),
                false,
            ],
            'object to class type is not contravariant'                                => [
                new Identifier('object'),
                new Identifier('AClass'),
                false,
            ],
            'class type to object is contravariant'                                    => [
                new Identifier('AClass'),
                new Identifier('object'),
                true,
            ],
            'mixed to object is not contravariant'                => [
                new Identifier('mixed'),
                new Identifier('object'),
                false,
            ],
            'object to mixed is contravariant'                => [
                new Identifier('object'),
                new Identifier('mixed'),
                true,
            ],
            'class type to scalar type is not contravariant'                           => [
                new Identifier('AClass'),
                new Identifier('string'),
                false,
            ],
            'scalar type to class type is not contravariant'                           => [
                new Identifier('string'),
                new Identifier('AClass'),
                false,
            ],
            'scalar type (string) to different scalar type (int) is not contravariant' => [
                new Identifier('string'),
                new Identifier('int'),
                false,
            ],
            'scalar type (int) to different scalar type (float) is not contravariant'  => [
                new Identifier('int'),
                new Identifier('float'),
                false,
            ],
            'object type to scalar type is not contravariant'                          => [
                new Identifier('object'),
                new Identifier('string'),
                false,
            ],
            'scalar type to object type is not contravariant'                          => [
                new Identifier('string'),
                new Identifier('object'),
                false,
            ],
            'class to superclass is contravariant'                                     => [
                new Identifier('BClass'),
                new Identifier('AClass'),
                true,
            ],
            'class to subclass is not contravariant'                                   => [
                new Identifier('BClass'),
                new Identifier('CClass'),
                false,
            ],
            'class to implemented interface is contravariant'                          => [
                new Identifier('AnotherClassWithMultipleInterfaces'),
                new Identifier('AnInterface'),
                true,
            ],
            'class to not implemented interface is not contravariant'                  => [
                new Identifier('AnotherClassWithMultipleInterfaces'),
                new Identifier('Traversable'),
                false,
            ],
            'interface to parent interface is contravariant'                           => [
                new Identifier('Iterator'),
                new Identifier('Traversable'),
                true,
            ],
            'interface to child interface is contravariant'                            => [
                new Identifier('Traversable'),
                new Identifier('Iterator'),
                false,
            ],

            'scalar type to union type is contravariant'                            => [
                new Identifier('int'),
                new UnionType([new Identifier('int'), new Identifier('string')]),
                true,
            ],
            'union type to scalar is not contravariant'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new Identifier('int'),
                false,
            ],
            'same union type is contravariant'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new UnionType([new Identifier('int'), new Identifier('string')]),
                true,
            ],
            'same union type (in reverse order) is covariant'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new UnionType([new Identifier('string'), new Identifier('int')]),
                true,
            ],
            'incompatible union types are not contravariant'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new UnionType([new Identifier('float'), new Identifier('bool')]),
                false,
            ],
            'union type to wider union type is contravariant - https://3v4l.org/jO1eE#v8.1rc3'                            => [
                new UnionType([new Identifier('int'), new Identifier('string')]),
                new UnionType([new Identifier('int'), new Identifier('string'), new Identifier('float')]),
                true,
            ],
            'union type to narrower union type is not contravariant - https://3v4l.org/SOUBk#v8.1rc3'                            => [
                new UnionType([new Identifier('int'), new Identifier('string'), new Identifier('float')]),
                new UnionType([new Identifier('int'), new Identifier('string')]),
                false,
            ],
            'nullable union type is equivalent to shorthand null definition'                   => [
                new UnionType([new Identifier('A'), new Identifier('null')]),
                new NullableType(new Identifier('A')),
                true,
            ],
            'shorthand nullable definition is equivalent to nullable union type'               => [
                new NullableType(new Identifier('A')),
                new UnionType([new Identifier('A'), new Identifier('null')]),
                true,
            ],
            'shorthand nullable definition to union type with added type is contravariant'     => [
                new NullableType(new Identifier('A')),
                new UnionType([new Identifier('A'), new Identifier('B'), new Identifier('null')]),
                true,
            ],
            'nullable union type to shorthand nullable definition is not contravariant'                => [
                new UnionType([new Identifier('A'), new Identifier('B'), new Identifier('null')]),
                new NullableType(new Identifier('A')),
                false,
            ],

            'object type to intersection type is not contravariant'                            => [
                new Identifier('A'),
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                false,
            ],
            'intersection type to object type is contravariant'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new Identifier('A'),
                true,
            ],
            'same intersection type is contravariant'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                true,
            ],
            'same intersection type (in reverse order) is contravariant'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new IntersectionType([new Identifier('B'), new Identifier('A')]),
                true,
            ],
            'incompatible intersection types are not contravariant'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new IntersectionType([new Identifier('C'), new Identifier('D')]),
                false,
            ],
            'intersection type to stricter intersection type is not contravariant - https://3v4l.org/pjnRe#v8.1rc3'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                new IntersectionType([new Identifier('A'), new Identifier('B'), new Identifier('C')]),
                false,
            ],
            'intersection type to less specific intersection type is contravariant - https://3v4l.org/2s8CW#v8.1rc3'                            => [
                new IntersectionType([new Identifier('A'), new Identifier('B'), new Identifier('C')]),
                new IntersectionType([new Identifier('A'), new Identifier('B')]),
                true,
            ],

            '(A&B)|(C&D) is contravariant to (A&B)|C - https://3v4l.org/9nsfZ#v8.2rc3'                            => [
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('B')]),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('B')]),
                    new Identifier('D'),
                ]),
                true,
            ],
            '(A&B)|(C&D) is contravariant to A|(C&D) - https://3v4l.org/BXb29#v8.2rc3'                            => [
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('B')]),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                new UnionType([
                    new Identifier('A'),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                true,
            ],
            '(A&B)|(C&D) is contravariant to B|(C&D) - https://3v4l.org/MO0mv#v8.2rc3'                            => [
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('B')]),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                new UnionType([
                    new Identifier('B'),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                true,
            ],
            '(A&B)|(C&D) is contravariant to A|D - https://3v4l.org/VQtE9#v8.2rc3'                            => [
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('B')]),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                new UnionType([
                    new Identifier('A'),
                    new Identifier('D'),
                ]),
                true,
            ],

            '(A&B)|(C&D) is not contravariant to A - https://3v4l.org/pqRUf#v8.2rc3'                            => [
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('B')]),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                new Identifier('A'),
                false,
            ],
            '(A&B)|(C&D) is not contravariant to A|B - https://3v4l.org/kiHPi#v8.2rc3'                            => [
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('B')]),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                new UnionType([
                    new Identifier('A'),
                    new Identifier('B'),
                ]),
                false,
            ],
            '(A&B)|(C&D) is not contravariant to (A&C)|(B&D) - https://3v4l.org/rQo1t#v8.2rc3'                            => [
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('B')]),
                    new IntersectionType([new Identifier('C'), new Identifier('D')]),
                ]),
                new UnionType([
                    new IntersectionType([new Identifier('A'), new Identifier('C')]),
                    new IntersectionType([new Identifier('B'), new Identifier('D')]),
                ]),
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
            $types,
        );
    }

    /** @dataProvider existingTypes */
    public function testContravarianceConsidersSameTypeAlwaysContravariant(
        ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null $type,
    ): void {
        self::assertTrue(
            (new TypeIsContravariant())($type, $type),
        );
    }

    /** @return list<array{ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType|null}> */
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
            (new BetterReflection())->astLocator(),
        ));

        $owner = Type\instance_of(ReflectionProperty::class)
            ->coerce(
                $reflector->reflectClass('OwnerPropertyContainer')
                    ->getProperty('owner'),
            );

        return array_merge(
            [[null]],
            array_merge(...array_map(
                static fn (string $type): array => [
                    [self::identifierType($reflector, $owner, new Identifier($type))],
                    [self::identifierType($reflector, $owner, new NullableType(new Identifier($type)))],
                ],
                [
                    'null',
                    'mixed',
                    'void',
                    'int',
                    'string',
                    'float',
                    'bool',
                    'true',
                    'false',
                    'array',
                    'iterable',
                    'callable',
                    'object',
                    'Traversable',
                    'AClass',
                ],
            )),
        );
    }

    /** @dataProvider existingNullableTypeStrings */
    public function testContravarianceConsidersNullability(string $type): void
    {
        $reflector = new DefaultReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface Traversable {}
class AClass {}
final class OwnerPropertyContainer { private $owner; }
PHP
            ,
            (new BetterReflection())->astLocator(),
        ));

        $owner = Type\instance_of(ReflectionProperty::class)
            ->coerce(
                $reflector->reflectClass('OwnerPropertyContainer')
                    ->getProperty('owner'),
            );

        $nullable    = self::identifierType($reflector, $owner, new NullableType(new Identifier($type)));
        $notNullable = self::identifierType($reflector, $owner, new Identifier($type));

        $isContravariant = new TypeIsContravariant();

        self::assertFalse($isContravariant($nullable, $notNullable));
        self::assertTrue($isContravariant($notNullable, $nullable));
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
            ['Traversable'],
            ['AClass'],
        ];
    }

    private static function identifierType(
        Reflector $reflector,
        ReflectionProperty $owner,
        Identifier|NullableType|UnionType|IntersectionType $identifier,
    ): ReflectionIntersectionType|ReflectionUnionType|ReflectionNamedType {
        return ReflectionType::createFromNode($reflector, $owner, $identifier);
    }
}
