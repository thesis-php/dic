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

You can read, rewire, and add tags during resolution, but every tag query is stabilized when it is first read.
After `$tags->find(CacheTag::class)`, adding another `CacheTag` fails the build because earlier consumers would have
already observed the list. Tags that have not been requested yet may still be added and picked up by later listeners.
After tag resolution finishes, no more listeners or tags may be added.

[`thesis/symfony-console-module`](https://github.com/thesis-php/symfony-console-module) ships two tags built this way:
`CommandTag` carries the command `name`, `description`, and `aliases` for invokable commands and functions;
`LegacyCommandTag` marks `Command` subclasses, optionally with a `name` to make them lazy.

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
  each `object()` service arrives as an `ObjectAutoconfig`:
  - **Inspect:** `$object->reflection` (`ReflectionClass`), `$object->attributes`, `is()`, `isInvokable()`
  - **Configure:** `tag()`, `disposer()`, `defaultScoped()`, `defaultCanBeScoped()`
  - **Methods:** `$object->methods` yields each public method as a
    [`MethodAutoconfig`](../src/Dic/Configuration/MethodAutoconfig.php)
    with its own `reflection`, `attributes`, and `register()` to enroll it as a `function()` service

- `onFunction(callable(`[`FunctionAutoconfig`](../src/Dic/Configuration/FunctionAutoconfig.php)`): void)` —
  each `function()` / `method()` service arrives as a `FunctionAutoconfig`:
  - **Inspect:** `$function->reflection` (`ReflectionFunction` or `ReflectionMethod`), `$function->attributes`
  - **Configure:** `tag()`, `disposer()`, `closure()` to adapt it to a typed `\Closure`

A convention is scoped to the module it is registered in: the listener visits only the services declared on the same
`Dic`, and a submodule pulled in with [`import()`](modularity.md) autoconfigures in isolation — its listeners never
reach your services, and yours never reach its.
Share one autoconfiguration scope across your own modules the same way you [share autowiring](modularity.md):
[`apply()`](modularity.md) them into the same `$dic`.

Only the introspectable kinds are visited — `object()`, `function()` and `method()`.
`value()`, `closure()`, `scoped()` and `taggedList()` are **not**:
a `value()` is an opaque carrier by design, and a `closure()` only reflects its declared signature,
not the implementation — the convention belongs on the `function()` it was built from.

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
- Services scheduled *by* a callback are visited too, but the loop terminates: a function callback only ever
  produces `closure()`s, which are not introspectable and so are never visited.

[`thesis/symfony-console-module`](https://github.com/thesis-php/symfony-console-module) ships this pattern as
`AutoconfigureCommands`: call `$dic->apply(new AutoconfigureCommands())` once, and every class and method annotated
with `#[AsCommand]` is tagged automatically in that module.
