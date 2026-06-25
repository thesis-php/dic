<?php

declare(strict_types=1);

namespace Thesis\Dic;

/**
 * Marker for every error thrown by the container.
 *
 * Catch this to handle any DIC error. To narrow by phase, catch
 * {@see Error\ConfigurationError} (build time) or {@see Error\RuntimeError} (resolution time),
 * or the underlying SPL bases (\LogicException / \RuntimeException).
 *
 * @api
 */
interface Error extends \Throwable {}
