<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return new Configuration()
    // PHPStan runs from the container's global vendor; tests/DicTypes.php uses its assertType() for type checks only
    ->ignoreUnknownFunctions(['PHPStan\Testing\assertType'])
    // revolt/event-loop is optional (see "suggest"): InstanceFuture checks that it is installed before using it
    ->ignoreErrorsOnPackageAndPath(
        'revolt/event-loop',
        __DIR__ . '/src/Dic/Internal/Container/InstanceFuture.php',
        [ErrorType::DEV_DEPENDENCY_IN_PROD],
    );
