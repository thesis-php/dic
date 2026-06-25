<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

/**
 * Signals a broken internal invariant — a bug in the library, never a user error.
 *
 * @internal
 */
final class ShouldNotHappen extends \LogicException {}
