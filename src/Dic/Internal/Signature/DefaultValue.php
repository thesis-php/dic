<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

/**
 * @internal
 */
interface DefaultValue
{
    public function create(): mixed;

    /**
     * @return non-empty-string
     */
    public function print(): string;
}
