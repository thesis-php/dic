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
`autoconfigure()` does it from a single rule.
The introspectable services are visited once, just before tags resolve,
and the callback receives each one's config —
an [`ObjectConfig`](../src/Dic/Configuration/ObjectConfig.php) or a
[`FunctionConfig`](../src/Dic/Configuration/FunctionConfig.php), the two kinds that expose real reflection:

- an `object()` service → `ObjectConfig`, whose `$service->reflection` is a `ReflectionClass`;
- a `function()` or `method()` service → `FunctionConfig`, whose `$service->reflection` is a
  `ReflectionFunction` / `ReflectionMethod`.

`value()`, `closure()`, `scoped()` and `taggedList()` are **not** visited:
a `value()` is an opaque carrier by design, and a `closure()` only reflects its declared signature,
not the implementation — the convention belongs on the `function()` it was built from.

From there you can `tag()` the service, give it a `disposer()`, `bind()` it, read its attributes
and declare further services (setting a lifetime needs an `instanceof ObjectConfig` check — see below).

The example below turns a routing convention into tagged closures.
Methods annotated with an `#[Action]` attribute become [`\Closure(Request): Response`](closure.md) services,
each tagged with the attribute it was found on, and finally collected into one list:

```php
use Thesis\Dic;
use Thesis\Dic\Configuration\FunctionConfig;
use Thesis\Dic\Configuration\ObjectConfig;
use Thesis\Dic\Tag;
use function Typhoon\Type\closureT;
use function Typhoon\Type\objectT;

/**
 * @implements Tag<callable(Request): Response>
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final readonly class Action implements Tag
{
    public function __construct(
        public string $path,
    ) {}
}

final readonly class Controller
{
    #[Action('/products')]
    public function list(Request $request): Response { /* … */ }
}

$actions = Dic::assemble(static function (Dic $dic): Dic\Ref {
    $dic->autoconfigure(static function (FunctionConfig|ObjectConfig $service) use ($dic): void {
        if (!$service instanceof ObjectConfig) {
            return;
        }

        foreach ($service->reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($method->getAttributes(Action::class) as $attribute) {
                $dic
                    ->function([$service, $method->name])
                    ->closure(closureT(params: [objectT(Request::class)], return: objectT(Response::class)))
                    ->tag($attribute->newInstance());
            }
        }
    });

    $dic->object(Controller::class);

    return $dic->taggedList(Action::class);
});
```

Note the order: the autoconfigurator visits `Controller`,
and from its `#[Action]` methods registers the tagged closures;
then the resolution phase collects them, which is why `taggedList(Action::class)` sees them all.

A service can opt out of every autoconfigurator with `doNotAutoconfigure()` —
useful when a broad rule would otherwise touch a service you want left alone:

```php
$dic
    ->object(LegacyController::class)
    ->doNotAutoconfigure(); // no actions derived from this one
```

Two more details worth knowing:

- Of the services an autoconfigurator sees, only `object()` carries a lifetime, so guard the call with an
  `instanceof` check against [`ObjectConfig`](../src/Dic/Configuration/ObjectConfig.php):
  `if ($service instanceof ObjectConfig) { $service->canBeScoped(); }`.
  A lifetime set this way is a **default** — an explicit lifetime on the service itself still wins.
- Services created *by* an autoconfigurator are not themselves autoconfigured,
  so a rule can't recurse into its own output.
