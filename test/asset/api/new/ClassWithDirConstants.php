<?php

declare(strict_types=1);

class ClassWithDirConstants
{
    public const valueWithDirDoesNotChange = __DIR__ . '/foo';
    public const valueWithDirDoesChange = __DIR__ . '/bar';
    public const valueWithFileDoesNotChange = __FILE__ . '/foo';
    public const valueWithFileDoesChange = __FILE__ . '/bar';
}
