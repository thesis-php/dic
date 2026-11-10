<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\ShouldNotHappen;
use function Thesis\Fixture\ref;

#[Test]
#[Covers(DependencyOrder::class)]
final class DependencyOrderTest
{
    public function dependentsFirstPutsServiceBeforeItsDependencies(): void
    {
        $order = new DependencyOrder();
        $order->append($dependency = ref(), []);
        $order->append($service = ref(), [$dependency]);
        $order->append($dependent = ref(), [$service, $dependency]);

        Assert::same(
            $order->dependentsFirst([$service, $dependency, $dependent]),
            [$dependent, $service, $dependency],
        );
    }

    public function dependentsFirstOrdersOnlyTheGivenRefs(): void
    {
        $order = new DependencyOrder();
        $order->append($dependency = ref(), []);
        $order->append($service = ref(), [$dependency]);
        $order->append($dependent = ref(), [$service]);

        Assert::same($order->dependentsFirst([$dependency, $dependent]), [$dependent, $dependency]);
    }

    #[ExpectException(ShouldNotHappen::class)]
    public function appendRejectsServiceWhoseDependencyIsNotOrderedYet(): void
    {
        new DependencyOrder()->append(ref(), [ref()]);
    }

    #[ExpectException(ShouldNotHappen::class)]
    public function appendRejectsAlreadyOrderedService(): void
    {
        $order = new DependencyOrder();
        $order->append($ref = ref(), []);

        $order->append($ref, []);
    }

    #[ExpectException(ShouldNotHappen::class)]
    public function dependentsFirstRejectsServiceThatIsNotOrdered(): void
    {
        new DependencyOrder()->dependentsFirst([ref()]);
    }
}
