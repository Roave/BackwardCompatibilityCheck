<?php

declare(strict_types=1);

(static function (): void {
    require_once __DIR__ . '/../vendor/autoload.php';

    // Clear $COMPOSER_HOME - depending on the used CI environment, $COMPOSER_HOME may be inaccessible,
    // or contain caches that are not usable for us: the safest bet is to not have a $COMPOSER_HOME at all.
    putenv('COMPOSER_HOME');
})();
