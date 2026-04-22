<?php

declare(strict_types=1);

namespace Thesis\DIC;

if (\defined('Thesis\DIC\doNotAutowire')) {
    return;
}

/**
 * @api
 */
const doNotAutowire = new Mapping\DoNotAutowire();
