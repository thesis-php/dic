<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

/**
 * @template-covariant T
 */
interface Factory
{
    /**
     * @return self<T>
     */
    public function autowire(Autowiring $autowiring): self;

    /**
     * @return T
     */
    public function make(Container $container): mixed;
}
