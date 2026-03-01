<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

/**
 * @internal
 *
 * @template-covariant T
 */
interface AutowireableFactory
{
    /**
     * @return self<T>
     */
    public function autowire(Autowiring $autowiring): self;

    /**
     * @return T
     */
    public function __invoke(Container $container): mixed;
}
