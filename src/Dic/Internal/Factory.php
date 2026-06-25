<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

/**
 * @internal
 *
 * @template-covariant T
 */
interface Factory
{
    /**
     * @return iterable<Dependency>
     */
    public function dependencies(): iterable;

    /**
     * @return T
     */
    public function create(Container $container): mixed;
}
