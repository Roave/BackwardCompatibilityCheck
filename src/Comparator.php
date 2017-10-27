<?php
declare(strict_types=1);

namespace Roave\ApiCompare;

use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;

final class Comparator
{
    public function compare(ClassReflector $oldApi, ClassReflector $newApi): array
    {
        $changelog = [];

        foreach ($oldApi->getAllClasses() as $oldClass) {
            try {
                $newClass = $newApi->reflect($oldClass->getName());
            } catch (IdentifierNotFound $exception) {
                $changelog[] = sprintf('[BC] Class %s has been deleted', $oldClass->getName());
                continue;
            }

            foreach ($oldClass->getMethods() as $oldMethod) {
                // @todo ignore private methods
                try {
                    $newMethod = $newClass->getMethod($oldMethod->getName());
                } catch (\OutOfBoundsException $exception) {
                    $changelog[] = sprintf(
                        '[BC] Method %s in class %s has been deleted',
                        $oldMethod->getName(),
                        $oldClass->getName()
                    );
                    continue;
                }

                foreach ($oldMethod->getParameters() as $oldParameter) {
                    $newParameter = $newMethod->getParameter($oldParameter->getName());

                    if (null === $newParameter) {
                        $changelog[] = sprintf(
                            '[BC] Parameter %s in %s%s%s has been deleted',
                            $oldParameter->getName(),
                            $oldClass->getName(),
                            $oldMethod->isStatic() ? '#' : '::',
                            $oldMethod->getName()
                        );
                        continue;
                    }

                    // @todo check if types changed, or becoming default
                    // @todo check if a new param (without a default) was added
                }
            }
        }

        return $changelog;
    }
}
