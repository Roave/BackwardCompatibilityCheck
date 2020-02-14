<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\ClassBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\PropertyChanged;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\PropertyBased\PropertyBased;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionProperty;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use RoaveTest\BackwardCompatibility\Assertion;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\ClassBased\PropertyChanged
 */
final class PropertyChangedTest extends TestCase
{
    public function testWillDetectChangesInProperties() : void
    {
        $astLocator = (new BetterReflection())->astLocator();

        $fromLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    public $a;
    protected $b;
    private $c;
    public static $d;
    public $G;
}
PHP
            ,
            $astLocator
        );

        $toLocator = new StringSourceLocator(
            <<<'PHP'
<?php

class TheClass {
    protected $b;
    public static $d;
    public $e;
    public $f;
    public $g;
}
PHP
            ,
            $astLocator
        );

        $comparator = $this->createMock(PropertyBased::class);

        $comparator
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(static function (ReflectionProperty $from, ReflectionProperty $to) : Changes {
                $propertyName = $from->getName();

                self::assertSame($propertyName, $to->getName());

                return Changes::fromList(Change::added($propertyName, true));
            });

        Assertion::assertChangesEqual(
            Changes::fromList(
                Change::added('b', true),
                Change::added('d', true)
            ),
            (new PropertyChanged($comparator))->__invoke(
                (new ClassReflector($fromLocator))->reflect('TheClass'),
                (new ClassReflector($toLocator))->reflect('TheClass')
            )
        );
    }

    /**
     * @dataProvider finalClassProvider
     */
    public function testWillIgnoreChangeInFinalClass($fromLocator, $toLocator) : void
    {


        $comparator = $this->createMock(PropertyBased::class);

        $comparator
            ->expects(self::exactly(2))
            ->method('__invoke')
            ->willReturnCallback(static function (ReflectionProperty $from, ReflectionProperty $to) : Changes {
                $propertyName = $from->getName();

                self::assertSame($propertyName, $to->getName());

                return Changes::fromList(Change::added($propertyName, true));
            });

        Assertion::assertChangesEqual(
            Changes::empty(),
            (new PropertyChanged($comparator))->__invoke(
                (new ClassReflector($fromLocator))->reflect('TheClass'),
                (new ClassReflector($toLocator))->reflect('TheClass')
            )
        );
    }

    public function finalClassProvider() : array
    {
        $astLocator = (new BetterReflection())->astLocator();

        return [
            'final class' => [
                new StringSourceLocator(
                    <<<'PHP'
<?php

final class TheClass {
    protected $b;
}
PHP
                    ,
                    $astLocator
                ),
                new StringSourceLocator(
                    <<<'PHP'
<?php
final class TheClass {
    private $b;
}
PHP
                    ,
                    $astLocator
                ),
            ],
            'final class extending baseclass' => [
                new StringSourceLocator(
                    <<<'PHP'
<?php
abstract class baseClass {}

final class TheClass extends baseClass {
    protected $b;
}
PHP
                    ,
                    $astLocator
                ),
                new StringSourceLocator(
                    <<<'PHP'
<?php
abstract class baseClass {}

final class TheClass extends baseClass{
    private $b;
}
PHP
                    ,
                    $astLocator
                ),
            ],
        ];

    }
}
