<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Thesis\Dic\Ref;

/**
 * @internal
 */
interface Container
{
    /**
     * @template T
     * @param Ref<T> $ref
     * @return T
     */
    public function get(Ref $ref): mixed;

    public function startScope(): Scope;

    public function dispose(?\Throwable $error = null): void;
}
