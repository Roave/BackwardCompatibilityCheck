<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithMethodVisibilitiesBeingChanged
{
    public function publicMaintainedPublic()
    {
    }
    protected function publicReducedToProtected()
    {
    }
    private function publicReducedToPrivate()
    {
    }
    protected function protectedMaintainedProtected()
    {
    }
    private function protectedReducedToPrivate()
    {
    }
    public function protectedIncreasedToPublic()
    {
    }
    private function privateMaintainedPrivate()
    {
    }
    protected function privateIncreasedToProtected()
    {
    }
    public function privateIncreasedToPublic()
    {
    }
    public function changedOrder1()
    {
    }
    private function changedOrder2()
    {
    }
}
