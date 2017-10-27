<?php
declare(strict_types=1);

namespace RoaveTest\ApiCompare;

use Roave\ApiCompare\Comparator;
use PHPUnit\Framework\TestCase;
use Roave\ApiCompare\Factory\DirectoryReflectorFactory;

/**
 * @covers \Roave\ApiCompare\Comparator
 */
final class ComparatorTest extends TestCase
{
    public function testCompare(): void
    {
        $reflectorFactory = new DirectoryReflectorFactory();
        self::assertSame(
            [
                '[BC] Parameter something in Thing::__construct has been deleted',
                '[BC] Method methodGone in class Thing has been deleted',
                '[BC] Class ClassGone has been deleted',
            ],
            (new Comparator())->compare(
                $reflectorFactory->__invoke(__DIR__ . '/../asset/api/old'),
                $reflectorFactory->__invoke(__DIR__ . '/../asset/api/new')
            )
        );
    }
}
