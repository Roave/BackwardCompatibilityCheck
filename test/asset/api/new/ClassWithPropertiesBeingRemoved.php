<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithPropertiesBeingRemoved
{
    public $NameCaseChangePublicProperty;
    public $keptPublicProperty;
    protected $NameCaseChangeProtectedProperty;
    protected $keptProtectedProperty;
    private $NameCaseChangePrivateProperty;
    private $keptPrivateProperty;
}
