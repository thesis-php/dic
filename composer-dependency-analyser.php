<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

return new Configuration()
    // PHPStan runs from the container's global vendor; tests/DicTypes.php uses its assertType() for type checks only
    ->ignoreUnknownFunctions(['PHPStan\Testing\assertType']);
