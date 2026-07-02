<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use function Thesis\Fixture\ref;

#[Test]
#[Covers(Dependency::class)]
final class DependencyTest
{
    public function ofHasEmptyPath(): void
    {
        $ref = ref();

        $dependency = Dependency::of($ref);

        Assert::same($dependency->path, '');
        Assert::same($dependency->ref, $ref);
    }

    public function factoryHasFactoryPath(): void
    {
        $dependency = Dependency::factory(ref());

        Assert::same($dependency->path, 'factory');
    }

    public function argPrependsParameterName(): void
    {
        $dependency = Dependency::of(ref())->arg('value');

        Assert::same($dependency->path, '$value');
    }

    public function keyPrependsBrackets(): void
    {
        Assert::same(Dependency::of(ref())->key(0)->path, '[0]');
        Assert::same(Dependency::of(ref())->key('name')->path, '[name]');
    }

    public function methodWrapsPath(): void
    {
        $dependency = Dependency::of(ref())->method('setLogger');

        Assert::same($dependency->path, '->setLogger()');
    }

    public function buildersNestFromInnerToOuter(): void
    {
        $ref = ref();

        Assert::same(Dependency::of($ref)->key(0)->arg('value')->path, '$value[0]');
        Assert::same(Dependency::of($ref)->arg('logger')->method('setLogger')->path, '->setLogger($logger)');
    }

    public function buildersPreserveRef(): void
    {
        $ref = ref();

        Assert::same(Dependency::of($ref)->arg('x')->key(1)->method('m')->ref, $ref);
    }
}
