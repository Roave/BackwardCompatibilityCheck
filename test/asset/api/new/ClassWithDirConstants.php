<?php

declare(strict_types=1);

class ClassWithDirConstants
{
    public const valueDoesNotChange = __DIR__ . '/foo';
    public const valueDoesChange = __DIR__ . '/bar';
}
