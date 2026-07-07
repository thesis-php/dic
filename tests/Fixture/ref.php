<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use Thesis\Dic\Configuration\ValueConfig;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Ref;
use function Thesis\Dic\Internal\caller;

/**
 * @template T
 * @param T $value
 * @return Ref<T>
 */
function ref(mixed $value = null): Ref
{
    return new ValueConfig(
        builder: new Builder(),
        autowiring: new Autowiring(),
        value: $value,
        declaredAt: caller(),
    );
}
