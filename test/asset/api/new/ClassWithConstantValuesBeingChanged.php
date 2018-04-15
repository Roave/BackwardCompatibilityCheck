<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithConstantValuesBeingChanged
{
    public const NameCaseChangePublicConstant = 'valueChanged';
    public const changedPublicConstant = 'valueChanged';
    public const preservedPublicConstant = 'value';
    public const preservedExpressionValuePublicConstant = 20;
    protected const NameCaseChangeProtectedConstant = 'valueChanged';
    protected const changedProtectedConstant = 'valueChanged';
    protected const preservedProtectedConstant = 'value';
    protected const preservedExpressionValueProtectedConstant = 20;
    private const NameCaseChangePrivateConstant = 'valueChanged';
    private const changedPrivateConstant = 'valueChanged';
    private const preservedPrivateConstant = 'value';
    private const preservedExpressionValuePrivateConstant = 20;
    public const publicConstantChangedToNull = null;
}
