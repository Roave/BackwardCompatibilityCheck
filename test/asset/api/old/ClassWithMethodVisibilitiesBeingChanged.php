<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithMethodVisibilitiesBeingChanged
{
    public function publicMaintainedPublic()
    {
    }
    public function publicReducedToProtected()
    {
    }
    public function publicReducedToPrivate()
    {
    }
    protected function protectedMaintainedProtected()
    {
    }
    protected function protectedReducedToPrivate()
    {
    }
    protected function protectedIncreasedToPublic()
    {
    }
    private function privateMaintainedPrivate()
    {
    }
    private function privateIncreasedToProtected()
    {
    }
    private function privateIncreasedToPublic()
    {
    }
}
