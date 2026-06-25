<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Error;

/**
 * An error raised while resolving services from a built container.
 *
 * @api
 */
abstract class RuntimeError extends \RuntimeException implements Error {}
