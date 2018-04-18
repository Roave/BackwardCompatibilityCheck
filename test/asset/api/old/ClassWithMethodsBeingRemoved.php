<?php

declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithMethodsBeingRemoved
{
    public function removedPublicMethod() : void
    {
    }
    public function nameCaseChangePublicMethod() : void
    {
    }
    public function keptPublicMethod() : void
    {
    }
    protected function removedProtectedMethod() : void
    {
    }
    protected function nameCaseChangeProtectedMethod() : void
    {
    }
    protected function keptProtectedMethod() : void
    {
    }
    private function removedPrivateMethod() : void
    {
    }
    private function nameCaseChangePrivateMethod() : void
    {
    }
    private function keptPrivateMethod() : void
    {
    }
}
