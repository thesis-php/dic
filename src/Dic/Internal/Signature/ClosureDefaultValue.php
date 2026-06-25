<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

/**
 * @internal
 */
enum ClosureDefaultValue implements DefaultValue
{
    case Value;

    public function create(): mixed
    {
        return $this;
    }

    public function print(): string
    {
        return \sprintf('%s::%s', self::class, $this->name);
    }
}
