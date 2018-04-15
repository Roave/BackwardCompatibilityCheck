<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithConstantVisibilitiesBeingChanged
{
    public const publicMaintainedPublic = 'value';
    protected const publicReducedToProtected = 'value';
    private const publicReducedToPrivate = 'value';
    protected const protectedMaintainedProtected = 'value';
    private const protectedReducedToPrivate = 'value';
    public const protectedIncreasedToPublic = 'value';
    private const privateMaintainedPrivate = 'value';
    protected const privateIncreasedToProtected = 'value';
    public const privateIncreasedToPublic = 'value';
}
