<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Dependency;
use function Thesis\Fixture\ref;

#[Covers(LifetimeStrategy::class)]
final class CanBeScopedStrategyTest
{
    #[Test]
    public function resolvesToSingletonWithoutDependencies(): void
    {
        $resolution = LifetimeStrategy::CanBeScoped->resolve(ref(), self::edges([]));

        Assert::same($resolution->strategy, LifetimeStrategy::CanBeScoped);
        Assert::true($resolution->isSingleton);
        Assert::null($resolution->scopedPath);
    }

    #[Test]
    public function staysSingletonWhenAllDependenciesAreSingletons(): void
    {
        $resolution = LifetimeStrategy::CanBeScoped->resolve(ref(), self::edges([
            [Dependency::of(ref()), Resolution::singleton(LifetimeStrategy::Singleton)],
            [Dependency::of(ref()), Resolution::singleton(LifetimeStrategy::Detached)],
        ]));

        Assert::same($resolution->strategy, LifetimeStrategy::CanBeScoped);
        Assert::true($resolution->isSingleton);
    }

    #[Test]
    public function becomesScopedFromTheFirstScopedDependency(): void
    {
        $dependency = Dependency::of(ref());

        $resolution = LifetimeStrategy::CanBeScoped->resolve(ref(), self::edges([
            [$dependency, Resolution::scoped(LifetimeStrategy::Scoped, [])],
        ]));

        Assert::same($resolution->strategy, LifetimeStrategy::CanBeScoped);
        Assert::false($resolution->isSingleton);
        Assert::same($resolution->scopedPath, [$dependency]);
    }

    #[Test]
    public function recordsThePathDescendingThroughAnInferredCarrier(): void
    {
        $carrier = Dependency::of(ref())->arg('caches');
        $leaf = Dependency::of(ref())->key(0);

        $resolution = LifetimeStrategy::CanBeScoped->resolve(ref(), self::edges([
            [$carrier, Resolution::scoped(LifetimeStrategy::Inferred, [$leaf])],
        ]));

        Assert::same($resolution->scopedPath, [$carrier, $leaf]);
    }

    #[Test]
    public function stopsAtTheFirstScopedDependency(): void
    {
        $first = Dependency::of(ref());

        $dependencies = (static function () use ($first): \Generator {
            yield $first => Resolution::scoped(LifetimeStrategy::Scoped, []);
            Assert::fail('Expected resolution to stop at the first scoped dependency.');
        })();

        $resolution = LifetimeStrategy::CanBeScoped->resolve(ref(), $dependencies);

        Assert::same($resolution->scopedPath, [$first]);
    }

    /**
     * @param list<array{Dependency, Resolution}> $edges
     * @return \Generator<Dependency, Resolution>
     */
    private static function edges(array $edges): \Generator
    {
        foreach ($edges as [$dependency, $resolution]) {
            yield $dependency => $resolution;
        }
    }
}
