<?php

declare(strict_types=1);

namespace Thesis\Dic\Configuration;

use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;
use Thesis\Dic\Internal\Autowiring;
use Thesis\Dic\Internal\Builder;
use Thesis\Dic\Internal\Builder\Autoconfiguration;
use Thesis\Dic\Location;
use Thesis\Fixture\ApcuCache;
use Thesis\Fixture\Cache;

#[Covers(ObjectAutoconfig::class)]
final class ObjectAutoconfigTest
{
    #[Test]
    public function isReturnsTrueForExactClass(): void
    {
        $autoconfig = self::autoconfig(ApcuCache::class);

        Assert::true($autoconfig->is(ApcuCache::class));
    }

    #[Test]
    public function isReturnsTrueForImplementedInterface(): void
    {
        $autoconfig = self::autoconfig(ApcuCache::class);

        Assert::true($autoconfig->is(Cache::class));
    }

    #[Test]
    public function isReturnsFalseForUnrelatedClass(): void
    {
        $autoconfig = self::autoconfig(ApcuCache::class);

        Assert::false($autoconfig->is(\stdClass::class));
    }

    /**
     * @param class-string $class
     */
    private static function autoconfig(string $class): ObjectAutoconfig
    {
        $builder = new Builder();
        $config = new ObjectConfig(
            builder: $builder,
            autoconfiguration: new Autoconfiguration($builder),
            autowiring: new Autowiring(),
            reflection: new \ReflectionClass($class),
            factory: null,
            declaredAt: Location::caller(),
        );

        return new ObjectAutoconfig($builder, $config);
    }
}
