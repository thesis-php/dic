<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Ref;

/**
 * @internal
 *
 * @template-covariant T = mixed
 */
interface Factory
{
    /**
     * @return iterable<string, Ref<*>>
     */
    public function dependencies(): iterable;

    /**
     * @return T
     */
    public function create(Container $container): mixed;
}
