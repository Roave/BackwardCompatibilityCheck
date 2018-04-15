<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithConstantValuesBeingChanged
{
    public const removedPublicConstant = 'value';
    public const nameCaseChangePublicConstant = 'value';
    public const changedPublicConstant = 'value';
    public const preservedPublicConstant = 'value';
    public const preservedExpressionValuePublicConstant = 10 * 2;
    protected const removedProtectedConstant = 'value';
    protected const nameCaseChangeProtectedConstant = 'value';
    protected const changedProtectedConstant = 'value';
    protected const preservedProtectedConstant = 'value';
    protected const preservedExpressionValueProtectedConstant = 10 * 2;
    private const removedPrivateConstant = 'value';
    private const nameCaseChangePrivateConstant = 'value';
    private const changedPrivateConstant = 'value';
    private const preservedPrivateConstant = 'value';
    private const preservedExpressionValuePrivateConstant = 10 * 2;
    public const publicConstantChangedToNull = 'value';
}
