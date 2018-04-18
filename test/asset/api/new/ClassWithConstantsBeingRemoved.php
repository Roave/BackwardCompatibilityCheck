<?php

declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithConstantsBeingRemoved
{
    public const NameCaseChangePublicConstant       = 'value';
    public const keptPublicConstant                 = 'value';
    protected const NameCaseChangeProtectedConstant = 'value';
    protected const keptProtectedConstant           = 'value';
    private const NameCaseChangePrivateConstant     = 'value';
    private const keptPrivateConstant               = 'value';
    private const changedOrder2                     = 'value';
    public const changedOrder1                      = 'value';
}
