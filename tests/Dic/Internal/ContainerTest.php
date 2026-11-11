<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Container\DependencyOrder;
use Thesis\Dic\Internal\Container\Disposers;
use Thesis\Dic\Internal\Container\Factories;
use Thesis\Dic\Internal\Factory\ValueFactory;
use Thesis\Dic\Ref;
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
        $container = new Container(
            factories: $factories,
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
        $container = new Container(
            factories: $factories,
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
        $container = new Container(
            factories: $factories,
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

    public function keepsTheFirstStoredInstanceWhenCreationCannotBeAwaited(): void
    {
        // A coroutine scheduler without fibers (Swoole) runs every coroutine as the main context,
        // so a concurrent request looks like the creating fiber asking for its own instance.
        $factories = new Factories();
        $container = new Container(
            factories: $factories,
            scopedFactories: new Factories(),
            dependencyOrder: new DependencyOrder(),
            disposers: new Disposers(),
        );
        $factory = new class implements Factory {
            /**
             * @var ?Ref<\stdClass>
             */
            public ?Ref $ref = null;

            public ?\stdClass $concurrent = null;

            public function dependencies(): iterable
            {
                return [];
            }

            public function create(Container $container): \stdClass
            {
                if ($this->ref !== null) {
                    $ref = $this->ref;
                    $this->ref = null;
                    $this->concurrent = $container->get($ref);
                }

                return new \stdClass();
            }
        };
        $ref = ref(new \stdClass());
        $factory->ref = $ref;
        $factories->register($ref, $factory);

        $first = $container->get($ref);

        Assert::instanceOf($factory->concurrent, \stdClass::class);
        Assert::same($first, $factory->concurrent);
        Assert::same($container->get($ref), $first);
    }
}
