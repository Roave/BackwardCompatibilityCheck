<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\Configuration;

use PHPUnit\Framework\TestCase;
use Psl\Env;
use Psl\File;
use Psl\Filesystem;
use Psl\Shell;
use Roave\BackwardCompatibility\Baseline;
use Roave\BackwardCompatibility\Configuration\Configuration;
use Roave\BackwardCompatibility\Configuration\InvalidConfigurationStructure;
use Roave\BackwardCompatibility\Configuration\ParseXmlConfigurationFile;

final class ParseXmlConfigurationFileTest extends TestCase
{
    private string $temporaryDirectory;

    /** @before */
    public function prepareFilesystem(): void
    {
        $this->temporaryDirectory = Filesystem\create_temporary_file(
            Env\temp_dir(),
            'roave-backward-compatibility-xml-config-test',
        );

        self::assertNotEmpty($this->temporaryDirectory);
        self::assertFileExists($this->temporaryDirectory);

        Filesystem\delete_file($this->temporaryDirectory);
        Filesystem\create_directory($this->temporaryDirectory);
    }

    /** @after */
    public function cleanUpFilesystem(): void
    {
        Shell\execute('rm', ['-rf', $this->temporaryDirectory]);
    }

    /** @test */
    public function defaultConfigurationShouldBeUsedWhenFileDoesNotExist(): void
    {
        $config = (new ParseXmlConfigurationFile())->parse($this->temporaryDirectory);

        self::assertEquals(Configuration::default(), $config);
    }

    /**
     * @test
     * @dataProvider invalidConfiguration
     */
    public function exceptionShouldBeRaisedWhenStructureIsInvalid(
        string $xmlContents,
        string $expectedError,
    ): void {
        File\write($this->temporaryDirectory . '/.roave-backward-compatibility-check.xml', $xmlContents);

        $this->expectException(InvalidConfigurationStructure::class);
        $this->expectExceptionMessage($expectedError);

        (new ParseXmlConfigurationFile())->parse($this->temporaryDirectory);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidConfiguration(): iterable
    {
        yield 'invalid root element' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<anything />
XML,
            '[Line 2] Element \'anything\': No matching global declaration available for the validation root',
        ];

        yield 'invalid root child' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<roave-bc-check>
    <something />
</roave-bc-check>
XML,
            '[Line 3] Element \'something\': This element is not expected. Expected is ( baseline )',
        ];

        yield 'multiple baseline tags' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<roave-bc-check>
    <baseline />
    <baseline />
</roave-bc-check>
XML,
            '[Line 4] Element \'baseline\': This element is not expected',
        ];

        yield 'invalid baseline child' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<roave-bc-check>
    <baseline>
        <nothing />
    </baseline>
</roave-bc-check>
XML,
            '[Line 4] Element \'nothing\': This element is not expected. Expected is ( ignored-regex )',
        ];

        yield 'invalid ignored item type' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<roave-bc-check>
    <baseline>
        <ignored-regex>
            <something-else />            
        </ignored-regex>
    </baseline>
</roave-bc-check>
XML,
            '[Line 4] Element \'ignored-regex\': Element content is not allowed, because the type definition is simple',
        ];
    }

    /**
     * @test
     * @dataProvider validConfiguration
     */
    public function baselineShouldBeParsed(
        string $xmlContents,
        Baseline $expectedBaseline,
    ): void {
        File\write($this->temporaryDirectory . '/.roave-backward-compatibility-check.xml', $xmlContents);

        self::assertEquals(
            Configuration::fromFile(
                $expectedBaseline,
                $this->temporaryDirectory . '/.roave-backward-compatibility-check.xml',
            ),
            (new ParseXmlConfigurationFile())->parse($this->temporaryDirectory),
        );
    }

    /** @return iterable<string, array{string, Baseline}> */
    public static function validConfiguration(): iterable
    {
        yield 'no baseline' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<roave-bc-check />
XML,
            Baseline::empty(),
        ];

        yield 'empty baseline' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<roave-bc-check>
    <baseline />
</roave-bc-check>
XML,
            Baseline::empty(),
        ];

        yield 'baseline with single element' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<roave-bc-check>
    <baseline>
        <ignored-regex>#\[BC\] CHANGED: The parameter \$a of TestArtifact\\TheClass\#method.*#</ignored-regex>
    </baseline>
</roave-bc-check>
XML,
            Baseline::fromList('#\[BC\] CHANGED: The parameter \$a of TestArtifact\\\\TheClass\#method.*#'),
        ];

        yield 'baseline with multiple elements' => [
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" ?>
<roave-bc-check>
    <baseline>
        <ignored-regex>#\[BC\] CHANGED: The parameter \$a of TestArtifact\\TheClass\#method.*#</ignored-regex>
        <ignored-regex>#\[BC\] ADDED: Method .*\(\) was added to interface TestArtifact\\TheInterface.*#</ignored-regex>
    </baseline>
</roave-bc-check>
XML,
            Baseline::fromList(
                '#\[BC\] CHANGED: The parameter \$a of TestArtifact\\\\TheClass\#method.*#',
                '#\[BC\] ADDED: Method .*\(\) was added to interface TestArtifact\\\\TheInterface.*#',
            ),
        ];
    }
}
