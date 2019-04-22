<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\LocateSources;

use baz\LocatedClass;
use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\LocateSources\LocateSourcesViaComposerJson;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;

/**
 * @covers \Roave\BackwardCompatibility\LocateSources\LocateSourcesViaComposerJson
 */
final class LocateSourcesViaComposerJsonTest extends TestCase
{
    /** @var LocateSourcesViaComposerJson */
    private $locateSources;

    protected function setUp() : void
    {
        parent::setUp();

        $this->locateSources = new LocateSourcesViaComposerJson((new BetterReflection())->astLocator());
    }

    public function testCanLocateClassInMappendAutoloadDefinitions() : void
    {
        $reflector = new ClassReflector(
            $this->locateSources
                ->__invoke(__DIR__ . '/../../asset/located-sources/composer-definition-with-everything')
        );

        self::assertSame(
            LocatedClass::class,
            $reflector
                ->reflect(LocatedClass::class)
                ->getName()
        );
    }
}
