# Autowiring

Autowiring resolves a non-variadic parameter to the service you have explicitly **bound** to that type —
nothing is scanned or guessed. You stay in control: a type resolves to a service only because you said so, 
and only within the module that said it.

## Binding a type

`bind()` declares a service as the implementation of a type.
Once `RedisCache` is bound to `Cache`, every parameter typed `Cache` resolves to it:

```php
use Thesis\Dic;
use Thesis\Dic\Module;
use function Typhoon\Type\objectT;

final readonly class CacheModule implements Dic
{
    public function configure(Dic $dic): void
    {
        $dic
            ->object(RedisCache::class)
            ->bind(objectT(Cache::class));
    
        $dic->object(CachedProductRepository::class); // its Cache parameter now resolves to RedisCache
    }
}
```

The type is a [`Typhoon\Type`](https://github.com/typhoon-php/type), so `objectT(Cache::class)` spells out `Cache`.
Any **native** PHP type can be bound — classes, interfaces, scalars (`intT`, `stringT`), unions, intersections.
Only non-native types are rejected: a generic such as `objectT(ArrayObject::class, [intT])`
fails with *is not supported for binding*.

A service is **not** bound to its own class automatically.
If a concrete class is injected by its own type, bind it to itself:

```php
$dic
    ->object(RedisCache::class)
    ->bind(objectT(RedisCache::class));
```

Without a matching binding, autowiring finds no candidate and the build fails with *cannot autowire*
(unless the parameter has a default value, which is then used).
Each type can be bound once: a second binding to the same type within the same module fails with *already bound*.

## Bindings are module-local

Bindings live on the module's `Dic`, not on the container as a whole.
`import()` hands the submodule a fresh `Dic` with an empty binding table,
so a submodule neither sees the parent's bindings nor leaks its own back up.

This keeps autowiring reasoning local: to know what a `Cache` parameter resolves to,
you only read the module that declares the service — never the whole application.
A module that wants a service from elsewhere imports its `Ref<T>` explicitly rather than relying on a shared binding.

This isolation is what `import()` buys you; modules can also be wired to share one autowiring table.
See [Modularity](modularity.md) for when to use each.

## Qualifiers

When one type has several implementations, tell them apart with a **qualifier** on each binding:

```php
$dic
    ->object(RedisCache::class)
    ->bind(objectT(Cache::class), qualifier: 'redis');

$dic
    ->object(ApcuCache::class)
    ->bind(objectT(Cache::class), qualifier: 'apcu');
```

A qualifier can be a `string`, a `Stringable`, or an enum:

```php
enum Caches
{
    case Redis;
    case Apcu;
}

$dic
    ->object(RedisCache::class)
    ->bind(objectT(Cache::class), qualifier: Caches::Redis);
```

A qualified binding never answers a plain `Cache` parameter,
so you select one explicitly per parameter — see below.

## Choosing per parameter

Each parameter carries two autowiring decisions: **which** binding to use, and **whether** to autowire it at all.
You can answer from the configuration — a marker value passed to [`arg()` / `args()`](arguments.md) —
or on the class — a parameter attribute. The two sides are equivalent:

| Decision                            | From the configuration | On the class              |
|-------------------------------------|------------------------|---------------------------|
| autowire by type only (the default) | `autowire`             | `#[Autowire]`             |
| autowire by type and qualifier      | `autowire($qualifier)` | `#[Autowire($qualifier)]` |
| skip autowiring                     | `doNotAutowire`        | `#[DoNotAutowire]`        |

From the configuration, markers are ordinary argument values:

```php
use function Thesis\Dic\autowire;
use const Thesis\Dic\doNotAutowire;

$dic
    ->object(ProductRepository::class)
    ->args([
        'cache' => autowire('apcu'), // pick the "apcu" binding
        'ttl' => doNotAutowire,      // keep the constructor default
    ]);
```

On the class, the same intent travels with the parameter:

```php
final readonly class ProductRepository
{
    public function __construct(
        #[Autowire('apcu')]
        public Cache $cache,
        #[DoNotAutowire]
        public int $ttl = 300,
    ) {}
}
```

`doNotAutowire` / `#[DoNotAutowire]` leaves the parameter to its default value (or an explicit `arg()`);
without one, the build fails with *cannot autowire*.

`#[Autowire]` and `#[DoNotAutowire]` contradict each other, so combining them on one parameter is rejected.

## Turning autowiring off for a whole service

To opt every parameter out at once, rather than one at a time, use the whole-service form — again on either side.
From the configuration, `doNotAutowire()`:

```php
$dic
    ->object(ProductRepository::class)
    ->doNotAutowire()
    ->arg('cache', $apcuCache);
```

On the class, `#[DoNotAutowire]` on the constructor (or any factory function/method):

```php
final readonly class ProductRepository
{
    #[DoNotAutowire]
    public function __construct(
        public ?Cache $cache = null,
        public int $ttl = 300,
    ) {}
}
```

Either form only flips the **default**: the service stops autowiring unless a parameter asks for it.
Individual parameters can still opt back in, overriding that default —
with `#[Autowire]` on the parameter, or `autowire` / `autowire($qualifier)` from the configuration:

```php
$dic
    ->object(ProductRepository::class)
    ->doNotAutowire()
    ->arg('cache', autowire('apcu')); // autowired despite the service default
```
