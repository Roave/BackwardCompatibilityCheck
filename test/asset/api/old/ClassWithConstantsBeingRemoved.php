<?php

declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithConstantsBeingRemoved
{
    public const removedPublicConstant              = 'value';
    public const nameCaseChangePublicConstant       = 'value';
    public const keptPublicConstant                 = 'value';
    protected const removedProtectedConstant        = 'value';
    protected const nameCaseChangeProtectedConstant = 'value';
    protected const keptProtectedConstant           = 'value';
    private const removedPrivateConstant            = 'value';
    private const nameCaseChangePrivateConstant     = 'value';
    private const keptPrivateConstant               = 'value';
}
