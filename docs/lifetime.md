# Lifetimes

A service has a **lifetime** that decides how often it is instantiated and how long it lives.

There are two layers at runtime:

- the **container** holds singletons — one instance each, shared for the whole life of the container;
- a **scope** is a short-lived child of the container that holds its own instances of scoped services.

A long-running application opens a fresh scope per unit of work — a request, a queue message, a job —
so scoped services live exactly as long as that work.

## Lifetimes

Every service is declared with one of three lifetimes:

- `singleton()` — the default; one shared instance.
- `scoped()` — one instance per scope.
- `canBeScoped()` — adaptive: scoped if it (transitively) depends on a scoped service, otherwise singleton.

```php
$dic
    ->object(Connection::class); // singleton by default

$dic
    ->object(RequestContext::class)
    ->scoped();
```

At build time Dic resolves every declared lifetime to a concrete one — singleton or scoped —
by walking the dependency graph.

These lifetimes belong to the services Dic instantiates — `object()` and `closure()`.
A [`value()`](value.md), a [`taggedList()`](tags.md) or a bare [`function()`](closure.md)/method ref takes no
lifetime of its own: it is transparent and inherits the lifetime of whatever it carries.

## A singleton may only depend on singletons

A singleton outlives every scope, so it must not capture a scoped instance.
Dic enforces this eagerly: if a `singleton()` service depends on a service declared `scoped()` or `canBeScoped()`,
the build fails with a clear error pointing at the offending edge.
This also covers the indirect case — a `value()` or `taggedList()` that carries a scoped service is itself scoped,
and the error follows the path through it to name the scoped service responsible.

The fix is to relax the dependent, not the dependency — make it `canBeScoped()` so it can follow its dependencies:

```php
$context = $dic
    ->object(RequestContext::class)
    ->scoped();

$dic
    ->object(RequestLogger::class)
    ->canBeScoped() // not the default singleton(), which would reject the scoped dependency
    ->arg('context', $context);
```

## How `canBeScoped()` resolves

`canBeScoped()` propagates scopedness up the graph:
the service becomes scoped if any of its dependencies resolves to scoped, and stays a singleton otherwise.

- depends only on singletons → resolves to **singleton** (one shared instance);
- depends on a scoped service → resolves to **scoped** (one instance per scope).

Why not just declare it `scoped()`?
Because a singleton is cheaper: it is built once and reused, while a scoped service is rebuilt for every scope.
`canBeScoped()` keeps that saving whenever the service turns out not to need a scope,
and pays for a fresh instance only when it actually holds a scoped dependency.

This fits services like controllers and message handlers.
They are happy to be shared singletons, but some of them accept a per-scope collaborator —
a transaction, a request context — and must then become scoped too.
`canBeScoped()` lets one declaration adapt either way,
so you don't have to track which handlers happen to pull in a scope.

## Choosing a lifetime

| Lifetime        | Use for                            | Examples                                            |
| --------------- | ---------------------------------- | --------------------------------------------------- |
| `singleton()`   | shared, stateless, costly to build | connection pool, HTTP client, logger, config        |
| `scoped()`      | per-unit-of-work state             | database transaction, request context, unit of work |
| `canBeScoped()` | adapts to its dependencies         | controllers, message handlers, application services |

A connection **pool** is a singleton: one pool serves the whole process.
A **transaction** is scoped: each unit of work gets its own, opened and disposed with the scope.
A **handler** is `canBeScoped()`: it stays a shared singleton
until it depends on a transaction (or another scoped service), at which point it becomes scoped to match.

## Running work in a scope: `Scoped<T>`

`$dic->scoped($ref)` is different from the `scoped()` lifetime above:
it declares a `Scoped<T>` **handle** that wraps another service.

```php
$worker = $dic->scoped(
    $dic->object(RequestContext::class)->scoped(),
); // Ref<Scoped<RequestContext>>
```

Inject the handle and call `run()` to open a fresh scope, resolve the wrapped service inside it,
run a callback, and dispose the scope:

```php
final readonly class Server
{
    /**
     * @param Scoped<RequestContext> $context
     */
    public function __construct(
        private Scoped $context,
    ) {}

    public function handle(): void
    {
        $this->context->run(static function (RequestContext $context): void {
            // fresh RequestContext per call, disposed when this returns
        });
    }
}
```

This is the key to long-running applications: `Server` itself is a singleton that lives for the whole process,
yet each `run()` gives it a fresh scope with fresh scoped instances.
A singleton may hold a `Scoped<T>` even when `T` is scoped — the handle defers resolution to the scope it opens,
so the singleton rule above is not violated.

## Concurrency

Creating an instance may suspend the current fiber — for example, on async I/O —
whether in a factory or in the constructor of an `object()` without one.
With [revolt/event-loop](https://github.com/revoltphp/event-loop) installed, every other fiber that asks for the same
instance meanwhile awaits that creation instead of starting its own:
a singleton is created once per container, a scoped service once per scope.
This works with amphp and any other library built on the Revolt event loop.

The first fiber creates the instance in place, so there is no extra fiber or event-loop tick
unless creation is actually contended.
If creation throws, every waiting fiber receives the same error, and the next request tries again.
Disposing a container or scope first awaits the instances still being created in other fibers, so they are disposed too.

Waiting suspends the fiber through the Revolt event loop, so fibers wait only while that loop runs —
once something has handed control to it, as amphp does on the first await.
Until then, and for coroutines that are not fibers (Swoole), each request creates its own instance;
the first one stored is kept and returned to every caller, the others are dropped without being disposed.
While the loop runs, every fiber must be scheduled by it: fibers started and resumed by hand are not supported.
Without revolt/event-loop, concurrent creation is never awaited and works the same way.

A [lazy](object.md#lazy-instantiation) service is not covered:
its proxy is created once, but the real instance is created when the proxy is first used, outside the container.
If that creation suspends, another fiber using the proxy meanwhile sees an uninitialized object
(reading a typed property throws an `Error`) — a limitation of PHP lazy objects.
Keep `lazy()` for services whose creation does not suspend.

## Disposal

Disposing a scope runs the [disposers](disposal.md) of every scoped instance it created;
disposing the container does the same for singletons.
Either way a service is disposed before the services it depends on — see [Disposal order](disposal.md#disposal-order).

- `Dic::run()` disposes its scope and then the container when `main` returns — and also if it throws.
- `Scoped::run()` disposes its scope after the callback, or on throw.
