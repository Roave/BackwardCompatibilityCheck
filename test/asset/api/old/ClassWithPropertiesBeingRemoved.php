<?php

declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithPropertiesBeingRemoved
{
    public $removedPublicProperty;
    public $nameCaseChangePublicProperty;
    public $keptPublicProperty;
    protected $removedProtectedProperty;
    protected $nameCaseChangeProtectedProperty;
    protected $keptProtectedProperty;
    private $removedPrivateProperty;
    private $nameCaseChangePrivateProperty;
    private $keptPrivateProperty;
}
