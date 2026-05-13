<?php

declare(strict_types=1);

namespace Thesis\Dic\Configurator;

use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\ContainerBuilder;
use Thesis\Dic\Internal\Factory;
use Thesis\Dic\Internal\Factory\Closure;
use Thesis\Dic\Lifetime;
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

    public Lifetime $lifetime { get => Lifetime::Singleton; }

    /**
     * @var \ReflectionClass<Scoped<*>>
     * @phpstan-ignore return.type
     */
    protected \ReflectionClass $reflection { get => new \ReflectionClass(Scoped::class); }

    /**
     * @internal
     *
     * @param Ref<T> $target
     */
    public function __construct(
        private readonly Ref $target,
        Location $declaredAt,
        Autowiring $autowiring,
        ContainerBuilder $containerBuilder,
    ) {
        parent::__construct(
            label: "scoped {$target}",
            declaredAt: $declaredAt,
            autowiring: $autowiring,
            containerBuilder: $containerBuilder,
        );
    }

    protected function createFactory(): Factory
    {
        $ref = $this->target;

        if ($this->target->lifetime !== Lifetime::Scoped) {
            throw new \LogicException(\sprintf(
                'Cannot wrap in Scoped a %s service %s',
                strtolower($ref->lifetime->name),
                $ref,
            ));
        }

        return new Closure(static fn(Container $c) => new Scoped($ref, $c));
    }
}
