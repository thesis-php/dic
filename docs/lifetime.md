# Lifetimes

A service has a **lifetime** that decides how often it is instantiated and how long it lives.

There are two layers at runtime:

- the **container** holds singletons — one instance each, shared for the whole life of the container;
- a **scope** is a short-lived child of the container that holds its own instances of scoped services.

A long-running application opens a fresh scope per unit of work — a request, a queue message, a job — so scoped services live exactly as long as that work.

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

At build time Dic resolves every declared lifetime to a concrete one — singleton or scoped — by walking the dependency graph.

## A singleton may only depend on singletons

A singleton outlives every scope, so it must not capture a scoped instance.
Dic enforces this eagerly: if a `singleton()` service depends on a service declared `scoped()` or `canBeScoped()`, the build fails with a clear error pointing at the offending edge.

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

`canBeScoped()` propagates scopedness up the graph: the service becomes scoped if any of its dependencies resolves to scoped, and stays a singleton otherwise.

- depends only on singletons → resolves to **singleton** (one shared instance);
- depends on a scoped service → resolves to **scoped** (one instance per scope).

Why not just declare it `scoped()`?
Because a singleton is cheaper: it is built once and reused, while a scoped service is rebuilt for every scope.
`canBeScoped()` keeps that saving whenever the service turns out not to need a scope, and pays for a fresh instance only when it actually holds a scoped dependency.

This fits services like controllers and message handlers.
They are happy to be shared singletons, but some of them accept a per-scope collaborator — a transaction, a request context — and must then become scoped too.
`canBeScoped()` lets one declaration adapt either way, so you don't have to track which handlers happen to pull in a scope.

## Choosing a lifetime

| Lifetime        | Use for                            | Examples                                            |
| --------------- | ---------------------------------- | --------------------------------------------------- |
| `singleton()`   | shared, stateless, costly to build | connection pool, HTTP client, logger, config        |
| `scoped()`      | per-unit-of-work state             | database transaction, request context, unit of work |
| `canBeScoped()` | adapts to its dependencies         | controllers, message handlers, application services |

A connection **pool** is a singleton: one pool serves the whole process.
A **transaction** is scoped: each unit of work gets its own, opened and disposed with the scope.
A **handler** is `canBeScoped()`: it stays a shared singleton until it depends on a transaction (or another scoped service), at which point it becomes scoped to match.

## Running work in a scope: `Scoped<T>`

`$dic->scoped($ref)` is different from the `scoped()` lifetime above: it declares a `Scoped<T>` **handle** that wraps another service.

```php
$worker = $dic->scoped(
    $dic->object(RequestContext::class)->scoped(),
); // Ref<Scoped<RequestContext>>
```

Inject the handle and call `run()` to open a fresh scope, resolve the wrapped service inside it, run a callback, and dispose the scope:

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

This is the key to long-running applications: `Server` itself is a singleton that lives for the whole process, yet each `run()` gives it a fresh scope with fresh scoped instances.
A singleton may hold a `Scoped<T>` even when `T` is scoped — the handle defers resolution to the scope it opens, so the singleton rule above is not violated.

## Disposal

Disposing a scope runs the [disposers](disposal.md) of every scoped instance it created; disposing the container does the same for singletons.

- `Dic::run()` disposes its scope and then the container when `main` returns — and also if it throws.
- `Scoped::run()` disposes its scope after the callback, or on throw.
