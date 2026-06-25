<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Error;

/**
 * A misconfiguration detected while building the container (wiring, lifetimes,
 * arguments, autowiring). Always fixable in the configuration code.
 *
 * @api
 */
abstract class ConfigurationError extends \LogicException implements Error {}
