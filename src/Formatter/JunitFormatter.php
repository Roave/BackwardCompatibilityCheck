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

class JunitFormatter implements OutputFormatter
{
    public function __construct(
        private OutputInterface $output,
        private CheckedOutRepository $basePath,
    ) {
    }

    public function write(Changes $changes): void
    {
        $basePath = $this->basePath->__toString() . '/';

        $changeCount = count($changes);

        $testcases = [];

        foreach ($changes as $change) {
            $filename = $change->file === null ? null : Str\replace($change->file, $basePath, '');

            $name = implode(':', [
                $this->escape($filename ?? ''),
                $this->escape((string) ($change->line ?? '')),
                $this->escape((string) ($change->column ?? '')),
            ]);

            $testcases[] = sprintf(
                '  <testcase name="%s"><failure type="error" message="%s"/></testcase>',
                $this->escape($name),
                $this->escape(trim($change->__toString())),
            );
        }

        $result = implode("\n", [
            '<?xml version="1.0" encoding="UTF-8"?>',
            sprintf(
                '<testsuite name="roave/backward-compatibility-check" tests="%d" failures="%d" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.9.1/platform-tests/src/test/resources/jenkins-junit.xsd">',
                $changeCount,
                $changeCount,
            ),
            ...$testcases,
            '</testsuite>',
        ]);

        $this->output->writeLn($result);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
