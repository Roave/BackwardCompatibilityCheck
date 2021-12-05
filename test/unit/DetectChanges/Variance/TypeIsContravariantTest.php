<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\Variance;

use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PHPUnit\Framework\TestCase;
use Psl\Type;
use Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflection\ReflectionType;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

use function array_map;
use function array_merge;

/** @covers \Roave\BackwardCompatibility\DetectChanges\Variance\TypeIsContravariant */
final class TypeIsContravariantTest extends TestCase
{
    /**
     * @dataProvider checkedTypes
     */
    public function testContravariance(
        ?ReflectionType $type,
        ?ReflectionType $newType,
        bool $expectedToBeContravariant
    ): void {
        self::assertSame(
            $expectedToBeContravariant,
            (new TypeIsContravariant())($type, $newType)
        );
    }

    /**
     * @return array<string, array{
     *     0: ReflectionType|null,
     *     1: ReflectionType|null,
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
            'no type to no type is contravariant with itself'                          => [
                null,
                null,
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
    public function testContravarianceConsidersSameTypeAlwaysContravariant(?ReflectionType $type): void
    {
        self::assertTrue(
            (new TypeIsContravariant())($type, $type)
        );
    }

    /** @return list<array{ReflectionType|null}> */
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

    /**
     * @dataProvider existingNullableTypeStrings
     */
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
            (new BetterReflection())->astLocator()
        ));

        $owner = Type\object(ReflectionProperty::class)
            ->coerce(
                $reflector->reflectClass('OwnerPropertyContainer')
                    ->getProperty('owner')
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
        Identifier|NullableType $identifier
    ): ReflectionType {
        return ReflectionType::createFromNode($reflector, $owner, $identifier);
    }
}
