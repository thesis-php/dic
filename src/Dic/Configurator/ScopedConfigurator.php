<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\ClassReflection;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Lifetime;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;
use Thesis\Dic\Scoped;

/**
 * @api
 *
 * @template T
 * @extends Ref<Scoped<T>>
 */
final class ScopedConfigurator extends Ref
{
    /** @use Internal\Bind<Scoped<T>> */
    use Internal\Bind;

    /** @use Internal\Tag<Scoped<T>> */
    use Internal\Tag;

    /** @use Internal\Disposer<Scoped<T>> */
    use Internal\Disposer;

    protected ClassReflection $internalReflection { get => ClassReflection::fromClass(Scoped::class); }

    /**
     * @internal
     *
     * @param Ref<T> $ref
     */
    public function __construct(
        private readonly Ref $ref,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        parent::__construct(
            label: "scoped {$ref}",
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
            lifetime: Lifetime::Singleton,
        );
    }

    protected function createFactory(): Factory
    {
        return new Factory\Scoped($this->ref);
    }
}
