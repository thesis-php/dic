<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use Thesis\Dic\Configuration\ValueConfig;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Typhoon\Type;

/**
 * @template T
 * @param Type<covariant T> $type
 * @return Ref<T>
 */
function ref(Type $type = Type\nullT): Ref
{
    /** @phpstan-ignore return.type */
    return new ValueConfig(
        builder: new Builder(),
        autowiring: new Autowiring(),
        value: null,
        declaredAt: Location::caller(),
    );
}
