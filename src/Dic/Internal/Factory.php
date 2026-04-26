<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

/**
 * @internal
 *
 * @template-covariant T = mixed
 */
interface Factory
{
    /**
     * @return T
     */
    public function create(Container $container): mixed;
}
