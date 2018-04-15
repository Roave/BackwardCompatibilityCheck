<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithPropertyVisibilitiesBeingChanged
{
    public $publicMaintainedPublic;
    protected $publicReducedToProtected;
    private $publicReducedToPrivate;
    protected $protectedMaintainedProtected;
    private $protectedReducedToPrivate;
    public $protectedIncreasedToPublic;
    private $privateMaintainedPrivate;
    protected $privateIncreasedToProtected;
    public $privateIncreasedToPublic;
    private $changedOrder2;
    public $changedOrder1;
}
