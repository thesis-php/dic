<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Configuration\Autoconfig;
use Thesis\Dic\Internal\NonCopyable;
use Thesis\Dic\Internal\Signature;

/**
 * @api
 *
 * @template-covariant T
 *
 * @phpstan-sealed Autoconfig
 */
abstract class Ref
{
    use NonCopyable;

    /**
     * @var non-empty-string
     */
    abstract public string $label { get; }

    abstract public Location $declaredAt { get; }

    abstract protected ?Signature $signature { get; }

    abstract public null|\ReflectionFunction|\ReflectionMethod $function { get; }

    /**
     * @var ?\ReflectionClass<*>
     */
    abstract public ?\ReflectionClass $class { get; }

    /**
     * @return non-empty-string
     */
    final public function __toString(): string
    {
        return \sprintf('"%s" (%s)', $this->label, $this->declaredAt);
    }
}
