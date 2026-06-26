<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal\Container;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use function Thesis\Fixture\ref;

#[Covers(Disposers::class)]
final class DisposersTest
{
    #[Test]
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

    #[Test]
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

    #[Test]
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
}
