<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Internal\Signature;

/**
 * @api
 *
 * @template-covariant T
 *
 * @phpstan-sealed \Thesis\Dic\Configuration\Config
 */
abstract class Ref
{
    /**
     * @param non-empty-string $label
     */
    public function __construct(
        public readonly string $label,
        public readonly Location $declaredAt,
    ) {}

    abstract protected ?Signature $signature { get; }

    abstract protected null|\ReflectionFunction|\ReflectionMethod $reflectionFunction { get; }

    /**
     * @var (T is object ? \ReflectionClass<covariant T> : null)
     */
    abstract protected ?\ReflectionClass $reflectionClass { get; }

    /**
     * @return non-empty-string
     */
    final public function __toString(): string
    {
        return \sprintf('"%s" (%s)', $this->label, $this->declaredAt);
    }
}
