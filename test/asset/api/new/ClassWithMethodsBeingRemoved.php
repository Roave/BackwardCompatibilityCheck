<?php
declare(strict_types=1);

namespace RoaveTestAsset;

class ClassWithMethodsBeingRemoved
{
    public function nameCaseChangePublicMethod() {
    }
    public function keptPublicMethod() {
    }
    protected function nameCaseChangeProtectedMethod() {
    }
    protected function keptProtectedMethod() {
    }
    private function nameCaseChangePrivateMethod() {
    }
    private function keptPrivateMethod() {
    }
}
