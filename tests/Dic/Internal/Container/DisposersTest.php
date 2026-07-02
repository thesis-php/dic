<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Fixture\Counter;
use function Thesis\Fixture\ref;

#[Test]
#[Covers(Disposers::class)]
final class DisposersTest
{
    public function disposeCallsDisposerWithValueAndError(): void
    {
        $disposers = new Disposers();
        $ref = ref();
        $error = new \RuntimeException('boom');
        $received = [];

        $disposers->add($ref, static function (mixed $value, ?\Throwable $error) use (&$received): void {
            $received = [$value, $error];
        });

        $disposers->dispose($ref, 'value', $error);

        Assert::same($received, ['value', $error]);
    }

    public function disposersRunInRegistrationOrder(): void
    {
        $disposers = new Disposers();
        $ref = ref();
        $log = [];

        $disposers->add($ref, static function () use (&$log): void {
            $log[] = 'a';
        });
        $disposers->add($ref, static function () use (&$log): void {
            $log[] = 'b';
        });

        $disposers->dispose($ref, null, null);

        Assert::same($log, ['a', 'b']);
    }

    public function disposeOnlyRunsDisposersForTheGivenRef(): void
    {
        $disposers = new Disposers();
        $ref = ref();
        $other = ref();
        $log = [];

        $disposers->add($other, static function () use (&$log): void {
            $log[] = 'other';
        });

        $disposers->dispose($ref, 'value', null);

        Assert::same($log, []);
    }

    public function disposeReturnsNoErrorsWithoutDisposers(): void
    {
        $disposers = new Disposers();

        Assert::same($disposers->dispose(ref(), 'value', null), []);
    }

    public function disposeCollectsThrowablesAndKeepsGoing(): void
    {
        $disposers = new Disposers();
        $ref = ref();
        $boom = new \RuntimeException('boom');
        $secondRan = false;

        $disposers->add($ref, static function () use ($boom): never {
            throw $boom;
        });
        $disposers->add($ref, static function () use (&$secondRan): void {
            $secondRan = true;
        });

        $errors = $disposers->dispose($ref, null, null);

        Assert::true($secondRan);
        Assert::same($errors, [$boom]);
    }

    public function disposeSkipsUninitializedLazyObject(): void
    {
        $disposers = new Disposers();
        $lazy = new \ReflectionClass(Counter::class)->newLazyProxy(static fn() => new Counter());
        $ref = ref($lazy);
        $called = false;
        $disposers->add($ref, static function () use (&$called): void {
            $called = true;
        });

        Assert::same($disposers->dispose($ref, $lazy, null), []);
        Assert::false($called);
    }
}
