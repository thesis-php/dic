<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Container;
use Thesis\Dic\Internal\Factory\ValueFactory;
use function Thesis\Fixture\ref;

#[Covers(Container::class)]
final class ContainerTest
{
    #[Test]
    public function disposesEveryCreatedInstance(): void
    {
        $factories = new Factories();
        $disposers = new Disposers();
        $container = new Singletons(
            singletonFactories: $factories,
            scopedFactories: new Factories(),
            disposers: $disposers,
        );
        $log = [];

        foreach (['a', 'b', 'c'] as $name) {
            $factories->register($ref = ref(), ValueFactory::from(new \stdClass()));
            $disposers->add($ref, static function () use (&$log, $name): void {
                $log[] = $name;
            });
            $container->get($ref);
        }

        Assert::same($container->dispose(null), []);
        Assert::same($log, ['a', 'b', 'c']);
    }

    #[Test]
    public function disposeIsIdempotent(): void
    {
        $factories = new Factories();
        $disposers = new Disposers();
        $container = new Singletons(
            singletonFactories: $factories,
            scopedFactories: new Factories(),
            disposers: $disposers,
        );
        $calls = 0;

        $factories->register($ref = ref(), ValueFactory::from(new \stdClass()));
        $disposers->add($ref, static function () use (&$calls): void {
            ++$calls;
        });
        $container->get($ref);

        $container->dispose(null);
        $container->dispose(null);

        Assert::same($calls, 1);
    }

    #[Test]
    public function disposesInstanceCreatedDuringDisposal(): void
    {
        $factories = new Factories();
        $disposers = new Disposers();
        $container = new Singletons(
            singletonFactories: $factories,
            scopedFactories: new Factories(),
            disposers: $disposers,
        );
        $log = [];

        $factories->register($late = ref(), ValueFactory::from(new \stdClass()));
        $disposers->add($late, static function () use (&$log): void {
            $log[] = 'late';
        });

        // Disposing $first resolves $late, creating it in the middle of disposal.
        $factories->register($first = ref(), ValueFactory::from(new \stdClass()));
        $disposers->add($first, static function () use (&$log, $container, $late): void {
            $log[] = 'first';
            $container->get($late);
        });
        $container->get($first);

        $container->dispose(null);

        Assert::same($log, ['first', 'late']);
    }
}
