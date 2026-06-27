# Closures

`closure()` declares a `\Closure` as a service.
Unlike `object()`, the thing you get back is a callable, and its `Ref` carries the full call signature,
so callers stay type-checked:

```php
use Thesis\Dic;
use function Typhoon\Type\closureT;
use function Typhoon\Type\objectT;

$handler = $dic->closure(
    closureT(params: [objectT(Request::class)], return: objectT(Response::class)),
    static fn(Request $request): Response => new Response(),
); // Ref<\Closure(Request): Response>
```

The first argument is the closure's **type** — a [`ClosureT`](https://github.com/typhoon-php/type)
spelling out the signature you expose: the parameters the caller passes at call time, and the return type.
The second is the **implementation** that produces the closure.

## Runtime parameters vs. dependencies

The implementation's parameters fall into two groups:

- those that match a parameter of the declared type (by name and type) are **runtime** parameters —
  the caller supplies them on each call;
- every other parameter is a **dependency**, resolved once when the closure is built —
  [autowired](autowiring.md) by default, or set by hand.

So a single implementation can mix injected services with per-call input:

```php
$dic
    ->object(RedisCache::class)
    ->bind(objectT(Cache::class));

$lookup = $dic->closure(
    closureT(params: [stringT()], return: nullOrT(stringT())),
    static fn(Cache $cache, string $key): ?string => $cache->get($key),
); // Ref<\Closure(string): ?string>
```

Here `$cache` is autowired (it is not part of the declared `\Closure(string): ?string` type),
while `$key` is the one runtime parameter the caller passes:

```php
$value = $lookup('user:1');
```

Dependencies take arguments exactly like an object service —
`arg()`, `args()`, `variadic()` and `doNotAutowire()` all apply,
and only ever touch dependencies, never runtime parameters.
See [Arguments](arguments.md):

```php
$dic
    ->closure(/* … */)
    ->arg('cache', $apcuCache); // wire the dependency explicitly instead of autowiring
```

## Implementations

The implementation can be any of:

- a plain `callable` — a closure, a function name, an invokable;
- a `[Ref<object>, 'method']` pair, to bind a method of another service as the closure;
- a `Ref<callable>` — another callable service.

```php
$controller = $dic->object(Controller::class);

$dic->closure(
    closureT(params: [objectT(Request::class)], return: objectT(Response::class)),
    [$controller, 'handle'], // call Controller::handle as the closure
);
```

A method can also be wired the other way around, from the object's own config, with `method()->closure()`:

```php
$dic
    ->object(Controller::class)
    ->method('handle')
    ->closure(closureT(params: [objectT(Request::class)], return: objectT(Response::class)));
```
