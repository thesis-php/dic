<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\Internal\ShouldNotHappen;
use function Thesis\Formatter\formatReflectedParameter;

/**
 * @internal
 */
final readonly class ReflectionDefaultValue implements DefaultValue
{
    public function __construct(
        private \ReflectionParameter $reflection,
    ) {
        \assert($this->reflection->isDefaultValueAvailable());
    }

    public function create(): mixed
    {
        return $this->reflection->getDefaultValue();
    }

    public function print(): string
    {
        if (preg_match('/(?<== )(.+) ]$/', (string) $this->reflection, $matches) !== 1) {
            throw new ShouldNotHappen(\sprintf(
                'Failed to parse default parameter %s: unexpected reflection string %s',
                formatReflectedParameter($this->reflection),
                $this->reflection,
            ));
        }

        return $matches[1];
    }
}
