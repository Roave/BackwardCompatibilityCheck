<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Configuration;

use DOMDocument;
use Psl\File;
use Roave\BackwardCompatibility\Baseline;
use SimpleXMLElement;

use function array_values;
use function libxml_get_errors;
use function libxml_use_internal_errors;

/** @internal */
final class ParseXmlConfigurationFile implements ParseConfigurationFile
{
    private const CONFIGURATION_FILENAME = '.roave-backward-compatibility-check.xml';

    private const SCHEMA = __DIR__ . '/../../resources/schema.xsd';

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

        $parsingErrors = array_values(libxml_get_errors());
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
            $ignoredItems[] = (string) $node;
        }

        return Baseline::fromList(...$ignoredItems);
    }
}
