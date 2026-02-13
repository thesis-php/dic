<?php

declare(strict_types=1);

namespace Thesis\DIC;

use function Typhoon\Formatter\format;

/**
 * @api
 *
 * @template-covariant T
 * @implements Reference<T>
 */
final readonly class Value implements Reference
{
    public Location $declaredAt;

    /**
     * @param T $value
     */
    public function __construct(
        public mixed $value,
        ?Location $declaredAt = null,
    ) {
        $this->declaredAt = $declaredAt ?? Location::fromBacktrace(-1);
    }

    public function __toString(): string
    {
        return \sprintf('[value `%s` at `%s`]', format($this->value), $this->declaredAt);
    }
}
