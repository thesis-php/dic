# Tags

A tag marks services so they can be collected as a group, without anyone naming each one.
The classic use is a plugin list: every handler tags itself, and one consumer picks them all up.

A tag is a class implementing the `Tag<T>` marker interface, where `T` is the type of the services it marks:

```php
use Thesis\Dic\Tag;

/**
 * @implements Tag<Cache>
 */
final readonly class CacheTag implements Tag {}
```

Attach it to a service with `tag()`:

```php
$dic->object(RedisCache::class)->tag(new CacheTag());
$dic->object(ApcuCache::class)->tag(new CacheTag());
```

A tag is an instance, not just a class, so it can carry data —
useful for priorities, routes, or any per-service metadata:

```php
/**
 * @implements Tag<Cache>
 */
final readonly class PriorityTag implements Tag
{
    public function __construct(
        public int $priority,
    ) {}
}

$dic->object(RedisCache::class)->tag(new PriorityTag(10));
```

## Collecting tagged services

`taggedList()` gathers every service carrying a tag into one `Ref<list<T>>`:

```php
$caches = $dic->taggedList(CacheTag::class); // Ref<list<Cache>>

$dic
    ->object(CacheChain::class)
    ->variadic($caches);
```

Passing a tag **class** matches every instance of it; passing a specific tag **instance** matches only that one.
The collected order follows declaration order unless you sort.

Pass a comparator as the second argument to order the list —
it receives two [`TaggedRef`](../src/Dic/TaggedRef.php)s, each exposing the matched `tag` and the service `ref`:

```php
use Thesis\Dic\TaggedRef;

$caches = $dic->taggedList(
    PriorityTag::class,
    static fn(TaggedRef $a, TaggedRef $b): int => $a->tag->priority <=> $b->tag->priority,
);
```

`taggedList()` pairs naturally with [`variadic()`](arguments.md), which unpacks the resolved list into a parameter.

## The resolution phase

Tags are gathered eagerly, but they are only *resolved* once, near the end of the build —
after every module has run and every service is declared, so a list always sees the complete set.
`taggedList()` is itself built on this phase; `onTagResolution()` lets you tap into it directly:

```php
use Thesis\Dic\TaggedRefs;

$dic->onTagResolution(static function (TaggedRefs $tags): void {
    foreach ($tags->find(CacheTag::class) as $tagged) {
        // $tagged->ref — the service Ref
        // $tagged->tag — the CacheTag instance
    }
});
```

The listener receives a [`TaggedRefs`](../src/Dic/TaggedRefs.php);
`find()` returns the `TaggedRef`s for a tag class or instance.
Because configs stay mutable until the container is built, a listener can wire what it finds back into a service —
this is the deferred-configuration pattern from [Arguments](arguments.md):

```php
$logger = $dic->object(Logger::class);

$dic->onTagResolution(static function (TaggedRefs $tags) use ($logger): void {
    $logger->variadic(array_map(
        static fn(TaggedRef $tagged) => $tagged->ref,
        $tags->find(LogHandlerTag::class),
    ));
});
```

You can read and rewire during resolution, but you cannot add new tags then —
tagging a service inside a resolution listener fails the build.

## Autoconfiguration

Tagging each service by hand is fine for a handful; for a convention applied across many services,
a callback applies it from one place.
Register a callback with `onObject()` (for `object()` services) or `onFunction()` (for `function()` / `method()`
services); each receives a small facade over the service, not the raw config.
A convention that spans both kinds is two callbacks — typically a pair of methods on your module,
passed as first-class callables, so there is no interface to implement and no `instanceof` dance:

```php
$dic->onObject($this->tagCommands(...));
$dic->onFunction($this->tagCommandFunctions(...));
```

- `onObject(callable(`[`ObjectAutoconfig`](../src/Dic/Configuration/ObjectAutoconfig.php)`): void)` —
  `$object->reflection` is its `ReflectionClass`, `$object->attributes` reads its class attributes, `$object->methods`
  lists its public methods; you can `tag()` it, give it a `disposer()`, set a default lifetime with `defaultScoped()` /
  `defaultCanBeScoped()`, or narrow it to a type with `is()` / `isInvokable()`.
- `onFunction(callable(`[`FunctionAutoconfig`](../src/Dic/Configuration/FunctionAutoconfig.php)`): void)` —
  `$function->reflection` is its `ReflectionFunction` / `ReflectionMethod`, `$function->attributes` reads its
  attributes; you can `tag()` it, give it a `disposer()`, or adapt it to a typed `\Closure` with `closure()`.

A convention is scoped to the module it is registered in: the listener visits only the services declared on the same
`Dic`, and a submodule pulled in with [`import()`](modularity.md) autoconfigures in isolation — its listeners never
reach your services, and yours never reach its.
Share one autoconfiguration scope across your own modules the same way you [share autowiring](modularity.md):
[`include()`](modularity.md) them into the same `$dic`.

Only the introspectable kinds are visited — `object()`, `function()` and `method()`.
`value()`, `closure()`, `scoped()` and `taggedList()` are **not**:
a `value()` is an opaque carrier by design, and a `closure()` only reflects its declared signature,
not the implementation — the convention belongs on the `function()` it was built from.

### Reaching a method: the two halves

An object callback cannot build a service from a method directly — it can only *schedule* the method with
`$method->autoconfigure()`, which registers it as a `function()` service.
That service then comes back through the `onFunction()` callback, where the real work happens.
The split keeps a single place that handles a callable: whether a method arrives by hand or via a convention, it is
configured exactly once in the function callback.

The example below turns a routing convention into tagged closures.
Methods annotated with an `#[Route]` attribute become [`\Closure(Request): Response`](closure.md) services,
each tagged with the attribute it was found on, and finally collected into one list:

```php
use Thesis\Dic;
use Thesis\Dic\Configuration\FunctionAutoconfig;
use Thesis\Dic\Configuration\ObjectAutoconfig;
use Thesis\Dic\Tag;
use function Typhoon\Type\closureT;
use function Typhoon\Type\objectT;

/**
 * @implements Tag<callable(Request): Response>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final readonly class Route implements Tag
{
    public function __construct(
        public string $path,
    ) {}
}

final readonly class Controller
{
    #[Route('/products')]
    public function list(Request $request): Response { /* … */ }
}

$actions = Dic::build(static function (Dic $dic): Dic\Ref {
    // Discover the action methods and schedule each one.
    $dic->onObject(static function (ObjectAutoconfig $object): void {
        foreach ($object->methods as $method) {
            if ($method->attributes->has(Route::class)) {
                $method->autoconfigure();
            }
        }
    });

    // A scheduled method arrives here: adapt it to a typed closure and tag it.
    $dic->onFunction(static function (FunctionAutoconfig $function): void {
        foreach ($function->attributes->all(Route::class) as $route) {
            $function
                ->closure(closureT(
                    params: [objectT(Request::class)],
                    return: objectT(Response::class),
                ))
                ->tag($route);
        }
    });

    $dic->object(Controller::class);

    return $dic->taggedList(Route::class);
});
```

Note the order: the object callback visits `Controller` and schedules its `#[Route]` methods,
the function callback then turns each into a tagged closure,
and the resolution phase collects them, which is why `taggedList(Route::class)` sees them all.
Any dependency an action method declares beyond the `Request` is [autowired](autowiring.md) into the closure.

A service can opt out of all autoconfiguration with `doNotAutoconfigure()` —
useful when a broad convention would otherwise touch a service you want left alone:

```php
$dic
    ->object(LegacyController::class)
    ->doNotAutoconfigure(); // no actions derived from this one
```

Two more details worth knowing:

- A lifetime set with `defaultScoped()` / `defaultCanBeScoped()` is a **default** —
  an explicit lifetime on the service itself still wins.
  Only the object callback offers them, since a `function()` always infers its lifetime from what it carries.
- Services scheduled *by* a callback are visited too — that is what makes the method round-trip work —
  but the loop terminates: a function callback only ever produces `closure()`s, which are not introspectable and so
  are never visited.
