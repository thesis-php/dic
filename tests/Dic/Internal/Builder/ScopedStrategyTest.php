<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Builder;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Dependency;
use function Thesis\Fixture\ref;

#[Test]
#[Covers(LifetimeStrategy::class)]
final class ScopedStrategyTest
{
    public function resolvesToScopedWithAnEmptyPath(): void
    {
        $resolution = LifetimeStrategy::Scoped->resolve(ref(), self::empty());

        Assert::same($resolution->strategy, LifetimeStrategy::Scoped);
        Assert::false($resolution->isSingleton);
        Assert::same($resolution->scopedPath, []);
    }

    public function ignoresDependencies(): void
    {
        $dependencies = (static function (): \Generator {
            Assert::fail('Dependencies must not be consumed.');
            yield; // @phpstan-ignore deadCode.unreachable
        })();

        $resolution = LifetimeStrategy::Scoped->resolve(ref(), $dependencies);

        Assert::same($resolution->scopedPath, []);
    }

    /**
     * @return \Generator<Dependency, Resolution>
     */
    private static function empty(): \Generator
    {
        yield from [];
    }
}
