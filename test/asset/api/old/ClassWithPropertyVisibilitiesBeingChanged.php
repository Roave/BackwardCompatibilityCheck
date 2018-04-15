<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithPropertyVisibilitiesBeingChanged
{
    public $publicMaintainedPublic;
    public $publicReducedToProtected;
    public $publicReducedToPrivate;
    protected $protectedMaintainedProtected;
    protected $protectedReducedToPrivate;
    protected $protectedIncreasedToPublic;
    private $privateMaintainedPrivate;
    private $privateIncreasedToProtected;
    private $privateIncreasedToPublic;
    public $changedOrder1;
    private $changedOrder2;
}
