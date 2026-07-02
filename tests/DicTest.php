<?php

declare(strict_types=1);

namespace Thesis;

use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Expect;
use Testo\Test;
use Thesis\Dic\BuildError;
use Thesis\Dic\ClosureModule;
use Thesis\Dic\DisposalFailed;
use Thesis\Dic\Ref;
use Thesis\Dic\TaggedRef;
use Thesis\Dic\TaggedRefs;
use Thesis\Fixture\ApcuCache;
use Thesis\Fixture\Cache;
use Thesis\Fixture\CacheStore;
use Thesis\Fixture\CacheTag;
use Thesis\Fixture\ConflictingConsumer;
use Thesis\Fixture\Consumer;
use Thesis\Fixture\Counter;
use Thesis\Fixture\CounterAndNumbers;
use Thesis\Fixture\CounterHolder;
use Thesis\Fixture\Holder;
use Thesis\Fixture\Numbers;
use Thesis\Fixture\OptionalConsumer;
use Thesis\Fixture\Pair;
use Thesis\Fixture\PriorityTag;
use Thesis\Fixture\QualifiedConsumer;
use Thesis\Fixture\RedisCache;
use Thesis\Fixture\WithDefault;
use function Thesis\Dic\autowire;
use function Typhoon\Type\closureT;
use function Typhoon\Type\objectT;
use function Typhoon\Type\param;
use const Typhoon\Type\intT;

#[Test]
final readonly class DicTest
{
    public function value(): void
    {
        $value = self::build(static fn(Dic $dic) => $dic->value(1));

        Assert::same($value, 1);
    }

    public function valueRef(): void
    {
        $value = self::build(static function (Dic $dic) {
            $ref = $dic->value(1);

            return $dic->value($ref);
        });

        Assert::same($value, 1);
    }

    public function valueArrayRef(): void
    {
        $value = self::build(static function (Dic $dic) {
            $ref1 = $dic->value(1);
            $ref2 = $dic->value(2);

            return $dic->value([$ref1, $ref2]);
        });

        Assert::same($value, [1, 2]);
    }

    public function object(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(TestService::class),
        );

        Assert::equals($object, new TestService());
    }

    public function objectStaticFactory(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(TestService::class, TestService::new(...)),
        );

        Assert::equals($object, TestService::new());
    }

    public function objectMethodFactory(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                factory: $dic->object(TestService::class)->method('new'),
            ),
        );

        Assert::equals($object, TestService::new());
    }

    public function objectCallableArrayClassMethodFactory(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                /** @phpstan-ignore argument.type */
                factory: $dic->value([
                    $dic->value(TestService::class),
                    $dic->value('new'),
                ]),
            ),
        );

        Assert::equals($object, TestService::new());
    }

    public function objectCallableArrayObjectMethodFactory(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                /** @phpstan-ignore argument.type */
                factory: $dic->value([
                    $dic->object(TestService::class),
                    $dic->value('new'),
                ]),
            ),
        );

        Assert::equals($object, TestService::new());
    }

    public function objectRefMethodArrayFactory(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(
                class: TestService::class,
                factory: [$dic->object(TestService::class), 'new'],
            ),
        );

        Assert::equals($object, TestService::new());
    }

    public function objectCall(): void
    {
        $value = 123;

        $object = self::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->call('set', [$value]),
        );

        Assert::same($object->value, $value);
    }

    public function objectCallWithVariadic(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->call('setAll', variadic: [1, 2, 3]),
        );

        Assert::same($object->value, [1, 2, 3]);
    }

    public function dependencyDeclaredAfterDependent(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->arg('value', $dic->object(TestService::class)),
        );

        Assert::equals($object->value, new TestService());
    }

    public function circularDependency(): void
    {
        $line = __LINE__;

        Expect::exception(BuildError::class)
            ->withMessage(\sprintf(
                <<<'MSG'
                    Circular dependency detected:

                      "Thesis\TestService" (tests/DicTest.php:%1$s)
                      └─ $value → "Thesis\TestService" (tests/DicTest.php:%2$s)
                         └─ $value → "Thesis\TestService" (tests/DicTest.php:%3$s)
                            └─ $value[0] → "Thesis\TestService" (tests/DicTest.php:%1$s)  ← cycle

                    Break the cycle by removing one of the dependencies above.
                    MSG,
                $line + 20,
                $line + 21,
                $line + 22,
            ));

        self::build(static function (Dic $dic) {
            $a = $dic->object(TestService::class);
            $b = $dic->object(TestService::class);
            $c = $dic->object(TestService::class);

            $a->arg('value', $b);
            $b->arg('value', $c);
            $c->arg('value', [$a]);

            return $a;
        });
    }

    #[ExpectException(\LogicException::class)]
    public function selfDependency(): void
    {
        self::build(static function (Dic $dic) {
            $service = $dic->object(TestService::class);

            return $service->arg('value', $service);
        });
    }

    #[ExpectException(\LogicException::class)]
    public function scopedCircularDependency(): void
    {
        self::build(static function (Dic $dic) {
            $a = $dic->object(TestService::class)->scoped();
            $b = $dic->object(TestService::class)->scoped();

            $a->arg('value', $b);
            $b->arg('value', $a);

            return $a;
        });
    }

    public function objectChain(): void
    {
        $value = 123;

        $object = self::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->chain('with', [$value]),
        );

        Assert::same($object->value, $value);
    }

    public function objectChainWithVariadic(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic
                ->object(TestService::class)
                ->chain('withAll', variadic: [1, 2, 3]),
        );

        Assert::same($object->value, [1, 2, 3]);
    }

    public function singletonScopedDependency(): void
    {
        $line = __LINE__;

        Expect::exception(BuildError::class)
            ->withMessage(\sprintf(
                <<<'MSG'
                    Singleton "Thesis\TestService" (tests/DicTest.php:%1$s) cannot depend on a non-singleton service:

                      "Thesis\TestService" (tests/DicTest.php:%1$s)
                      └─ $factory → "Thesis\TestService" (tests/DicTest.php:%2$s)  ← Scoped

                    Make "Thesis\TestService" (tests/DicTest.php:%1$s) scoped (or canBeScoped()), or make the dependency a singleton.
                    MSG,
                $line + 20,
                $line + 17,
            ));

        self::build(static function (Dic $dic) {
            $scoped = $dic->object(TestService::class)->scoped();

            return $dic
                ->object(TestService::class)
                ->args([$scoped]);
        });
    }

    public function autowiresConstructorByBoundType(): void
    {
        $consumer = self::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class));

            return $dic->object(Consumer::class);
        });

        Assert::instanceOf($consumer->cache, RedisCache::class);
        Assert::same($consumer->cache->get('key'), 'redis');
    }

    public function bindWithQualifierSelectsImplementation(): void
    {
        $consumer = self::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class), 'redis');
            $dic->object(ApcuCache::class)->bind(objectT(Cache::class), 'apcu');

            return $dic->object(Consumer::class)->arg('cache', autowire('apcu'));
        });

        Assert::instanceOf($consumer->cache, ApcuCache::class);
    }

    public function singletonInstanceIsSharedBetweenDependents(): void
    {
        $pair = self::build(static function (Dic $dic) {
            $dic->object(Holder::class)->bind(objectT(Holder::class));

            return $dic->object(Pair::class);
        });

        Assert::same($pair->first, $pair->second);
    }

    public function defaultValueIsUsedWhenNotAutowirable(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(WithDefault::class),
        );

        Assert::same($object->number, 42);
    }

    public function argOverridesDefaultValue(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(WithDefault::class)->arg('number', 7),
        );

        Assert::same($object->number, 7);
    }

    public function variadicArguments(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(Numbers::class)->variadic([1, 2, 3]),
        );

        Assert::same($object->numbers, [1, 2, 3]);
    }

    public function emptyVariadicDefaultsToEmptyArray(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(Numbers::class),
        );

        Assert::same($object->numbers, []);
    }

    public function requireComposesSubmodule(): void
    {
        $value = self::build(
            static fn(Dic $dic) => $dic->import(
                new ClosureModule(static fn(Dic $inner) => $inner->value(42)),
            ),
        );

        Assert::same($value, 42);
    }

    public function lazyObjectIsUsable(): void
    {
        $counter = self::build(
            static fn(Dic $dic) => $dic->object(Counter::class)->lazy(),
        );

        Assert::instanceOf($counter, Counter::class);

        $counter->value = 5;

        Assert::same($counter->value, 5);
    }

    public function lazyOnNonInstantiableClassRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('cannot be made lazy');

        self::build(
            static fn(Dic $dic) => $dic
                ->object(Cache::class, static fn(): Cache => new RedisCache())
                ->lazy(),
        );
    }

    public function scopedWrapperProducesFreshInstancePerRun(): void
    {
        $scoped = self::build(
            static fn(Dic $dic) => $dic->scoped(
                $dic->object(Counter::class)->scoped(),
            ),
        );

        $first = $scoped->run(static fn(Counter $counter) => $counter);
        $second = $scoped->run(static fn(Counter $counter) => $counter);

        Assert::notSame($first, $second);
    }

    public function runInvokesDisposerWithValue(): void
    {
        $disposed = [];

        $result = Dic::run(
            new ClosureModule(static function (Dic $dic) use (&$disposed) {
                return $dic->object(Counter::class)->disposer(
                    static function (Counter $counter) use (&$disposed): void {
                        $disposed[] = $counter;
                    },
                );
            }),
            static fn(Counter $counter) => $counter->value = 1,
        );

        Assert::same($result, 1);
        Assert::count($disposed, 1);
    }

    public function runPassesThrownErrorToDisposerAndRethrows(): void
    {
        $thrown = new \RuntimeException('boom');
        $captured = null;
        $rethrown = null;

        try {
            Dic::run(
                new ClosureModule(static function (Dic $dic) use (&$captured) {
                    return $dic->object(Counter::class)->disposer(
                        static function (Counter $counter, ?\Throwable $error) use (&$captured): void {
                            $captured = $error;
                        },
                    );
                }),
                static function (Counter $counter) use ($thrown): never {
                    throw $thrown;
                },
            );
        } catch (\RuntimeException $error) {
            $rethrown = $error;
        }

        Assert::same($rethrown, $thrown);
        Assert::same($captured, $thrown);
    }

    public function disposerExceptionsDoNotStopOtherDisposers(): void
    {
        $boom = new \RuntimeException('boom');
        $secondRan = false;
        $caught = null;

        try {
            Dic::run(
                new ClosureModule(static function (Dic $dic) use ($boom, &$secondRan) {
                    return $dic
                        ->object(Counter::class)
                        ->disposer(static function () use ($boom): never {
                            throw $boom;
                        })
                        ->disposer(static function () use (&$secondRan): void {
                            $secondRan = true;
                        });
                }),
                static fn(Counter $counter) => null,
            );
        } catch (DisposalFailed $error) {
            $caught = $error;
        }

        Assert::true($secondRan);
        Assert::notNull($caught);
        Assert::same($caught->errors, [$boom]);
    }

    public function disposerExceptionDoesNotMaskMainError(): void
    {
        $mainError = new \RuntimeException('main');
        $disposerError = new \RuntimeException('disposer');
        $caught = null;

        try {
            Dic::run(
                new ClosureModule(
                    static fn(Dic $dic) => $dic
                        ->object(Counter::class)
                        ->disposer(static function () use ($disposerError): never {
                            throw $disposerError;
                        }),
                ),
                static function (Counter $counter) use ($mainError): never {
                    throw $mainError;
                },
            );
        } catch (DisposalFailed $error) {
            $caught = $error;
        }

        Assert::same($caught->getPrevious(), $mainError);
        Assert::same($caught->errors, [$disposerError]);
    }

    public function unusedLazyServiceIsNotDisposed(): void
    {
        $disposed = false;

        Dic::run(
            new ClosureModule(static function (Dic $dic) use (&$disposed) {
                $counter = $dic
                    ->object(Counter::class)
                    ->lazy()
                    ->disposer(static function () use (&$disposed): void {
                        $disposed = true;
                    });

                return $dic->object(CounterHolder::class)->arg('counter', $counter);
            }),
            static fn(CounterHolder $holder) => null,
        );

        Assert::false($disposed);
    }

    public function usedLazyServiceIsDisposed(): void
    {
        $disposed = false;

        Dic::run(
            new ClosureModule(static function (Dic $dic) use (&$disposed) {
                $counter = $dic
                    ->object(Counter::class)
                    ->lazy()
                    ->disposer(static function () use (&$disposed): void {
                        $disposed = true;
                    });

                return $dic->object(CounterHolder::class)->arg('counter', $counter);
            }),
            static function (CounterHolder $holder): void {
                $holder->counter->value = 1; // touch the lazy proxy to initialize it
            },
        );

        Assert::true($disposed);
    }

    public function taggedListCollectsTaggedServices(): void
    {
        $caches = self::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->tag(new CacheTag());
            $dic->object(ApcuCache::class)->tag(new CacheTag());

            return $dic->taggedList(CacheTag::class);
        });

        Assert::count($caches, 2);
        Assert::contains(
            array_map(static fn(Cache $cache) => $cache->get('key'), $caches),
            'redis',
        );
    }

    public function taggedListRespectsSort(): void
    {
        $caches = self::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->tag(new PriorityTag(2));
            $dic->object(ApcuCache::class)->tag(new PriorityTag(1));

            return $dic->taggedList(
                PriorityTag::class,
                static fn(TaggedRef $a, TaggedRef $b): int => $a->tag->priority <=> $b->tag->priority,
            );
        });

        Assert::same(
            array_map(static fn(Cache $cache) => $cache->get('key'), $caches),
            ['apcu', 'redis'],
        );
    }

    public function onResolveTagsReceivesTaggedRefs(): void
    {
        $found = null;

        self::build(static function (Dic $dic) use (&$found) {
            $dic->object(RedisCache::class)->tag(new CacheTag());

            $dic->onTagResolution(static function (TaggedRefs $taggedRefs) use (&$found): void {
                $found = $taggedRefs->find(CacheTag::class);
            });

            return $dic->value(null);
        });

        Assert::notNull($found);
        Assert::count($found, 1);
    }

    public function signatureWiresFunctionArguments(): void
    {
        $signature = closureT([param(intT, name: 'n')], intT);

        $function = self::build(static function (Dic $dic) use ($signature) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class));

            return $dic
                ->function(static fn(Cache $cache, int $n): int => \strlen($cache->get('key') ?? '') + $n)
                ->closure($signature);
        });

        Assert::same($function(10), 15);
    }

    public function signatureWithVariadicRuntimeParameter(): void
    {
        $signature = closureT([param(intT, variadic: true, name: 'numbers')], intT);

        $function = self::build(
            static fn(Dic $dic) => $dic
                ->function(static fn(int ...$numbers): int => array_sum($numbers))
                ->closure($signature),
        );

        Assert::same($function(1, 2, 3), 6);
    }

    public function attributeAutowireQualifierSelectsImplementation(): void
    {
        $consumer = self::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class), 'redis');
            $dic->object(ApcuCache::class)->bind(objectT(Cache::class), 'apcu');

            return $dic->object(QualifiedConsumer::class);
        });

        Assert::instanceOf($consumer->cache, ApcuCache::class);
    }

    public function attributeDoNotAutowireFallsBackToDefault(): void
    {
        $consumer = self::build(
            static fn(Dic $dic) => $dic->object(OptionalConsumer::class),
        );

        Assert::null($consumer->cache);
    }

    public function bindWithEnumQualifier(): void
    {
        $consumer = self::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class), CacheStore::Redis);
            $dic->object(ApcuCache::class)->bind(objectT(Cache::class), CacheStore::Apcu);

            return $dic->object(Consumer::class)->arg('cache', autowire(CacheStore::Apcu));
        });

        Assert::instanceOf($consumer->cache, ApcuCache::class);
    }

    public function canBeScopedStaysSingletonWithoutScopedDependencies(): void
    {
        $scoped = self::build(
            static fn(Dic $dic) => $dic->scoped(
                $dic->object(Counter::class)->canBeScoped(),
            ),
        );

        $first = $scoped->run(static fn(Counter $counter) => $counter);
        $second = $scoped->run(static fn(Counter $counter) => $counter);

        Assert::same($first, $second);
    }

    public function canBeScopedBecomesScopedWithScopedDependency(): void
    {
        $scoped = self::build(
            static fn(Dic $dic) => $dic->scoped(
                $dic
                    ->object(CounterHolder::class)
                    ->canBeScoped()
                    ->arg('counter', $dic->object(Counter::class)->scoped()),
            ),
        );

        $first = $scoped->run(static fn(CounterHolder $holder) => $holder);
        $second = $scoped->run(static fn(CounterHolder $holder) => $holder);

        Assert::notSame($first, $second);
    }

    public function positionalArgument(): void
    {
        $object = self::build(
            static fn(Dic $dic) => $dic->object(WithDefault::class)->arg(0, 7),
        );

        Assert::same($object->number, 7);
    }

    public function positionalFactoryWiresDependencyAndVariadic(): void
    {
        $object = self::build(static function (Dic $dic) {
            $dic->object(Counter::class)->bind(objectT(Counter::class));

            return $dic->object(CounterAndNumbers::class)->variadic([1, 2]);
        });

        Assert::instanceOf($object->counter, Counter::class);
        Assert::same($object->numbers, [1, 2]);
    }

    public function eagerObjectIsUsable(): void
    {
        $counter = self::build(
            static fn(Dic $dic) => $dic->object(Counter::class)->lazy()->eager(),
        );

        Assert::instanceOf($counter, Counter::class);

        $counter->value = 3;

        Assert::same($counter->value, 3);
    }

    public function scopedRunDisposesAndRethrowsOnError(): void
    {
        $thrown = new \RuntimeException('boom');
        $captured = null;
        $rethrown = null;

        $scoped = self::build(
            static function (Dic $dic) use (&$captured) {
                return $dic->scoped(
                    $dic
                        ->object(Counter::class)
                        ->scoped()
                        ->disposer(static function (Counter $counter, ?\Throwable $error) use (&$captured): void {
                            $captured = $error;
                        }),
                );
            },
        );

        try {
            $scoped->run(static function (Counter $counter) use ($thrown): never {
                throw $thrown;
            });
        } catch (\RuntimeException $error) {
            $rethrown = $error;
        }

        Assert::same($rethrown, $thrown);
        Assert::same($captured, $thrown);
    }

    public function taggedListFindsByTagInstance(): void
    {
        $tag = new CacheTag();

        $caches = self::build(static function (Dic $dic) use ($tag) {
            $dic->object(RedisCache::class)->tag($tag);
            $dic->object(ApcuCache::class)->tag(new CacheTag());

            return $dic->taggedList($tag);
        });

        Assert::count($caches, 1);
    }

    public function emptyTaggedListIsEmptyArray(): void
    {
        $caches = self::build(
            static fn(Dic $dic) => $dic->taggedList(CacheTag::class),
        );

        Assert::same($caches, []);
    }

    public function unboundDependencyWrapsCannotAutowire(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('cannot autowire');

        self::build(static fn(Dic $dic) => $dic->object(Consumer::class));
    }

    public function nonInstantiableClassRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('is not instantiable');

        self::build(static fn(Dic $dic) => $dic->object(Cache::class));
    }

    public function factoryRefThatIsNotCallableRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('is not callable');

        self::build(
            /** @phpstan-ignore argument.type */
            static fn(Dic $dic) => $dic->object(Counter::class, factory: $dic->value(42)),
        );
    }

    public function refMethodFactoryFails(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('is not callable');

        self::build(static function (Dic $dic) {
            $factory = $dic->object(TestService::class);

            return $dic->object(TestService::class, factory: [$factory, '__toString']);
        });
    }

    public function duplicateBindRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('already bound');

        self::build(static function (Dic $dic) {
            $dic->object(RedisCache::class)->bind(objectT(Cache::class));
            $dic->object(ApcuCache::class)->bind(objectT(Cache::class));

            return $dic->value(null);
        });
    }

    public function bindUnsupportedTypeRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('is not supported for binding');

        self::build(static function (Dic $dic) {
            $dic->value(new \ArrayObject())->bind(objectT(\ArrayObject::class, [intT]));

            return $dic->value(null);
        });
    }

    public function combinedAutowireAndDoNotAutowireRejected(): void
    {
        Expect::exception(BuildError::class)->withMessageContaining('combine #[Autowire] and #[DoNotAutowire]');

        self::build(static fn(Dic $dic) => $dic->object(ConflictingConsumer::class));
    }

    /**
     * @template T
     * @param \Closure(Dic): Ref<T> $app
     * @return T
     */
    private static function build(\Closure $app): mixed
    {
        return Dic::build(new ClosureModule($app));
    }
}
