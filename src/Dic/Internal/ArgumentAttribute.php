<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Autowire;
use Thesis\Dic\DoNotAutowire;

/**
 * @internal
 *
 * @phpstan-sealed Autowire|DoNotAutowire
 */
interface ArgumentAttribute {}
