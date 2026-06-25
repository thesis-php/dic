<?php

declare(strict_types=1);

namespace Thesis\Dic\Internal;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Configuration\ValueConfig;
use Thesis\Dic\Location;
use Thesis\Dic\Ref;

#[Covers(Dependency::class)]
final class DependencyTest
{
    #[Test]
    public function ofHasEmptyPath(): void
    {
        $ref = self::ref();

        $dependency = Dependency::of($ref);

        Assert::same($dependency->path, '');
        Assert::same($dependency->ref, $ref);
    }

    #[Test]
    public function factoryHasFactoryPath(): void
    {
        $dependency = Dependency::factory(self::ref());

        Assert::same($dependency->path, 'factory');
    }

    #[Test]
    public function argPrependsParameterName(): void
    {
        $dependency = Dependency::of(self::ref())->arg('value');

        Assert::same($dependency->path, '$value');
    }

    #[Test]
    public function keyPrependsBrackets(): void
    {
        Assert::same(Dependency::of(self::ref())->key(0)->path, '[0]');
        Assert::same(Dependency::of(self::ref())->key('name')->path, '[name]');
    }

    #[Test]
    public function methodWrapsPath(): void
    {
        $dependency = Dependency::of(self::ref())->method('setLogger');

        Assert::same($dependency->path, '->setLogger()');
    }

    #[Test]
    public function buildersNestFromInnerToOuter(): void
    {
        $ref = self::ref();

        Assert::same(Dependency::of($ref)->key(0)->arg('value')->path, '$value[0]');
        Assert::same(Dependency::of($ref)->arg('logger')->method('setLogger')->path, '->setLogger($logger)');
    }

    #[Test]
    public function buildersPreserveRef(): void
    {
        $ref = self::ref();

        Assert::same(Dependency::of($ref)->arg('x')->key(1)->method('m')->ref, $ref);
    }

    /**
     * @return Ref<int>
     */
    private static function ref(): Ref
    {
        return new ValueConfig(
            builder: new Builder(),
            autowiring: new Autowiring(),
            value: 1,
            declaredAt: Location::caller(),
        );
    }
}
