<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal\Container;

use Thesis\DIC\Tags;

/**
 * @internal
 */
interface Subscriber
{
    /**
     * @param callable(Tags): void $listener
     */
    public function onResolveTags(callable $listener): void;

    /**
     * @param callable(ServiceRegistrar): void $listener
     */
    public function onBeforeAssemble(callable $listener): void;

    /**
     * @param callable(): void $listener
     */
    public function onAfterAssemble(callable $listener): void;
}
