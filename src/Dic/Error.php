<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * Base class for every error thrown by the container at build time.
 *
 * @api
 */
abstract class Error extends \LogicException {}
