<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithMethodsBeingRemoved
{
    public function removedPublicMethod() {
    }
    public function nameCaseChangePublicMethod() {
    }
    public function keptPublicMethod() {
    }
    protected function removedProtectedMethod() {
    }
    protected function nameCaseChangeProtectedMethod() {
    }
    protected function keptProtectedMethod() {
    }
    private function removedPrivateMethod() {
    }
    private function nameCaseChangePrivateMethod() {
    }
    private function keptPrivateMethod() {
    }
}
