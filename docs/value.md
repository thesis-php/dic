# Values

`value()` declares a ready-made value as a service and returns its `Ref<T>`.
Use it for anything you already hold — configuration, a constant, a pre-built object —
so it can be injected, imported or exported like any other service:

```php
$timeout = $dic->value(30); // Ref<int>

$dic
    ->object(HttpClient::class)
    ->arg('timeout', $timeout);
```

The `Ref<T>` keeps `T` inferred from the value, so a `value(30)` is a `Ref<int>`
and the argument it feeds stays type-checked.

## Refs inside values

A value may itself be a `Ref`, or an array mixing plain values and refs at any depth.
Every nested ref is resolved when the value is built, in place:

```php
$redis = $dic->object(RedisCache::class);
$apcu = $dic->object(ApcuCache::class);

$caches = $dic->value([
    'redis' => $redis, // Ref, resolved to a RedisCache
    'apcu' => $apcu,   // Ref, resolved to an ApcuCache
    'default' => 'redis',
]);
```

The service built from `$caches` is the same array with each ref replaced by its service:
`['redis' => RedisCache, 'apcu' => ApcuCache, 'default' => 'redis']`.
Wrapping a `Ref` in `value()` is a no-op pass-through — `value($ref)` builds to exactly what `$ref` builds to.

## Binding a value

A `ValueConfig` can be bound to a type for [autowiring](autowiring.md), tagged or given a disposer.
This makes `value()` the natural way to autowire a scalar or array:

```php
$dic
    ->value(30)
    ->bind(intT); // every int parameter now autowires to 30
```

## Lifetime

A value has no lifetime of its own, so it exposes no `singleton()` / `scoped()` / `canBeScoped()`.
It is a transparent carrier that takes on the [lifetime](lifetime.md) of the refs it holds:
plain data (or a value holding only singletons) is a singleton,
while a value that carries a scoped service is itself scoped — and so cannot be captured by a singleton.
