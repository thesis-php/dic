<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory\ValueFactory;
use function Thesis\Fixture\ref;

#[Test]
#[Covers(Container::class)]
final class ContainerTest
{
    public function disposesDependentsBeforeTheirDependencies(): void
    {
        $factories = new Factories();
        $dependencyOrder = new DependencyOrder();
        $disposers = new Disposers();
        $container = new Singletons(
            singletonFactories: $factories,
            scopedFactories: new Factories(),
            dependencyOrder: $dependencyOrder,
            disposers: $disposers,
        );
        $log = [];
        $refs = [];

        foreach (['dependency', 'service', 'dependent'] as $name) {
            $factories->register($ref = ref(), ValueFactory::from(new \stdClass()));
            $dependencyOrder->append($ref, $refs);
            $refs[] = $ref;
            $disposers->add($ref, static function () use (&$log, $name): void {
                $log[] = $name;
            });
        }

        // The creation order must not matter.
        $container->get($refs[1]);
        $container->get($refs[2]);
        $container->get($refs[0]);

        Assert::same($container->dispose(null), []);
        Assert::same($log, ['dependent', 'service', 'dependency']);
    }

    public function disposeIsIdempotent(): void
    {
        $factories = new Factories();
        $dependencyOrder = new DependencyOrder();
        $disposers = new Disposers();
        $container = new Singletons(
            singletonFactories: $factories,
            scopedFactories: new Factories(),
            dependencyOrder: $dependencyOrder,
            disposers: $disposers,
        );
        $calls = 0;

        $factories->register($ref = ref(), ValueFactory::from(new \stdClass()));
        $dependencyOrder->append($ref, []);
        $disposers->add($ref, static function () use (&$calls): void {
            ++$calls;
        });
        $container->get($ref);

        $container->dispose(null);
        $container->dispose(null);

        Assert::same($calls, 1);
    }

    public function disposesInstanceCreatedDuringDisposal(): void
    {
        $factories = new Factories();
        $dependencyOrder = new DependencyOrder();
        $disposers = new Disposers();
        $container = new Singletons(
            singletonFactories: $factories,
            scopedFactories: new Factories(),
            dependencyOrder: $dependencyOrder,
            disposers: $disposers,
        );
        $log = [];

        $factories->register($late = ref(), ValueFactory::from(new \stdClass()));
        $dependencyOrder->append($late, []);
        $disposers->add($late, static function () use (&$log): void {
            $log[] = 'late';
        });

        // Disposing $first resolves $late, creating it in the middle of disposal.
        $factories->register($first = ref(), ValueFactory::from(new \stdClass()));
        $dependencyOrder->append($first, []);
        $disposers->add($first, static function () use (&$log, $container, $late): void {
            $log[] = 'first';
            $container->get($late);
        });
        $container->get($first);

        $container->dispose(null);

        Assert::same($log, ['first', 'late']);
    }
}
