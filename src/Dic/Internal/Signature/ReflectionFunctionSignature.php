<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Signature;

use Thesis\Dic\DoNotAutowire;
use Thesis\Dic\Internal\Signature;

/**
 * @internal
 *
 * @template-covariant TReflection of \ReflectionFunction|\ReflectionMethod
 */
final class ReflectionFunctionSignature extends Signature
{
    /**
     * @param TReflection $reflection
     */
    protected function __construct(
        public readonly \ReflectionFunction|\ReflectionMethod $reflection,
    ) {}

    private bool $isAutowiringModeSet = false;

    public ?DoNotAutowire $autowiringMode = null {
        get {
            if (!$this->isAutowiringModeSet) {
                $this->autowiringMode = array_first($this->reflection->getAttributes(DoNotAutowire::class))?->newInstance();
                $this->isAutowiringModeSet = true;
            }

            return $this->autowiringMode;
        }
    }

    /**
     * @phpstan-ignore property.uninitialized
     */
    public private(set) array $parameters {
        get => $this->parameters ??= array_map(
            static fn(\ReflectionParameter $reflection) => new ReflectionParameter($reflection),
            $this->reflection->getParameters(),
        );
    }
}
