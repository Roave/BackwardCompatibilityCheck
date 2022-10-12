<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\Formatter;

use Psl\Str;
use Roave\BackwardCompatibility\Changes;
use Roave\BackwardCompatibility\Git\CheckedOutRepository;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function htmlspecialchars;
use function implode;
use function sprintf;
use function trim;

use const ENT_COMPAT;
use const ENT_XML1;

/**
 * String concatenatation is used rather than DOMDocument or simplexml
 * as that would cause the formatter to be dependent on the DOM php
 * extension, which may not be available in all environments.
 */
final class JunitFormatter implements OutputFormatter
{
    public function __construct(
        private readonly OutputInterface $output,
        private readonly CheckedOutRepository $basePath,
    ) {
    }

    public function write(Changes $changes): void
    {
        $basePath = $this->basePath->__toString() . '/';

        $changeCount = count($changes);

        $this->output->writeLn('<?xml version="1.0" encoding="UTF-8"?>');
        $this->output->writeLn(sprintf(
            '<testsuite name="roave/backward-compatibility-check" tests="%d" failures="%d" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/732a5400f80c8f446daa8b43eaa4b41b3da929be/platform-tests/src/test/resources/jenkins-junit.xsd">',
            $changeCount,
            $changeCount,
        ));

        foreach ($changes as $change) {
            $filename = $change->file === null ? null : Str\replace($change->file, $basePath, '');

            $name = $this->escapeXmlAttribute(implode(':', [
                $filename ?? '',
                (string) ($change->line ?? ''),
                (string) ($change->column ?? ''),
            ]));

            $this->output->writeLn(sprintf(
                '  <testcase name="%s"><failure type="error" message="%s"/></testcase>',
                $this->escapeXmlAttribute($name),
                $this->escapeXmlAttribute(trim($change->__toString())),
            ));
        }

        $this->output->writeLn('</testsuite>');
    }

    private function escapeXmlAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
