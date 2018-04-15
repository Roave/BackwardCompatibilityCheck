<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithConstantVisibilitiesBeingChanged
{
    public const publicMaintainedPublic = 'value';
    public const publicReducedToProtected = 'value';
    public const publicReducedToPrivate = 'value';
    protected const protectedMaintainedProtected = 'value';
    protected const protectedReducedToPrivate = 'value';
    protected const protectedIncreasedToPublic = 'value';
    private const privateMaintainedPrivate = 'value';
    private const privateIncreasedToProtected = 'value';
    private const privateIncreasedToPublic = 'value';
    public const changedOrder1 = 'value';
    private const changedOrder2 = 'value';
}
