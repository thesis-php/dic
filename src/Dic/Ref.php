<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Configuration\Autoconfig;

/**
 * @api
 *
 * @template-covariant T
 *
 * @phpstan-sealed Autoconfig
 */
interface Ref
{
    /**
     * @var non-empty-string
     */
    public string $label { get; }

    public Location $declaredAt { get; }

    /**
     * @var (T is callable ? \ReflectionFunction|\ReflectionMethod : null)
     */
    public null|\ReflectionFunction|\ReflectionMethod $function { get; }

    /**
     * @var (T is object ? \ReflectionClass<covariant T> : null)
     */
    public ?\ReflectionClass $class { get; }

    /**
     * @return non-empty-string
     */
    public function __toString(): string;
}
