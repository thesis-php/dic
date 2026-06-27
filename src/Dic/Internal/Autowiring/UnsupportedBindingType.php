<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Autowiring;

use Thesis\Dic\BuildError;

/**
 * @internal
 *
 * A type cannot be used for autowiring/binding. Raised and caught internally as
 * a control-flow signal, so it lives outside the {@see BuildError} type.
 */
final class UnsupportedBindingType extends \LogicException {}
