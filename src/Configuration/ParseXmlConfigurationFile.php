<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Configuration;

use DOMDocument;
use Psl\File;
use Roave\BackwardCompatibility\Baseline;
use SimpleXMLElement;

use function assert;
use function libxml_get_errors;
use function libxml_use_internal_errors;

/** @internal */
final class ParseXmlConfigurationFile implements ParseConfigurationFile
{
    private const string CONFIGURATION_FILENAME = '.roave-backward-compatibility-check.xml';

    private const string SCHEMA = __DIR__ . '/../../Resources/schema.xsd';

    public function parse(string $currentDirectory): Configuration
    {
        $filename = $currentDirectory . '/' . self::CONFIGURATION_FILENAME;

        try {
            $xmlContents = File\read($filename);

            $this->validateStructure($xmlContents);
        } catch (File\Exception\InvalidArgumentException) {
            return Configuration::default();
        }

        $configuration = new SimpleXMLElement($xmlContents);

        return Configuration::fromFile(
            $this->parseBaseline($configuration),
            $filename,
        );
    }

    private function validateStructure(string $xmlContents): void
    {
        $previousConfiguration = libxml_use_internal_errors(true);

        $xmlDocument = new DOMDocument();
        $xmlDocument->loadXML($xmlContents);

        $configurationIsValid = $xmlDocument->schemaValidate(self::SCHEMA);

        $parsingErrors = libxml_get_errors();
        libxml_use_internal_errors($previousConfiguration);

        if ($configurationIsValid) {
            return;
        }

        throw InvalidConfigurationStructure::fromLibxmlErrors($parsingErrors);
    }

    private function parseBaseline(SimpleXMLElement $element): Baseline
    {
        $ignoredItems = [];

        foreach ($element->xpath('baseline/ignored-regex') ?? [] as $node) {
            $ignoredItem = (string) $node;

            assert($ignoredItem !== '');
            $ignoredItems[] = $ignoredItem;
        }

        return Baseline::fromList(...$ignoredItems);
    }
}
