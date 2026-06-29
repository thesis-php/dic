# Functions and closures

`function()` declares a `callable` as a service and returns its `Ref<callable>` — the callable as-is:

```php
use Thesis\Dic;

$trim = $dic->function(trim(...)); // Ref<callable>
```

Left like this the callable is **transparent**: the container stores it and hands it out unchanged, and wires
nothing into it — its parameters are entirely the caller's to pass.
There is nothing to configure, so a bare `function()` takes no arguments and no lifetime.

## Adapting to a signature: `closure()`

To turn the callable into a typed `\Closure` service whose dependencies the container resolves,
apply it to a declared signature with `closure()`:

```php
use function Typhoon\Type\closureT;
use function Typhoon\Type\objectT;

$handler = $dic
    ->function(static fn(Request $request): Response => new Response())
    ->closure(closureT(params: [objectT(Request::class)], return: objectT(Response::class)));
// Ref<\Closure(Request): Response>
```

`closure()` takes the closure's **type** — a [`ClosureT`](https://github.com/typhoon-php/type)
spelling out the signature you expose: the parameters the caller passes at call time, and the return type.
The `Ref` carries that signature, so callers stay type-checked.

The two steps are the two modes of a function service:

- `function()` alone — the callable as-is, nothing wired in, no arguments;
- `function()->closure(type)` — once a signature is declared the result is known,
  so the container autowires the rest and you can set arguments.

## Runtime parameters vs. dependencies

Against the declared type, the implementation's parameters fall into two groups:

- those that match a parameter of the declared type (by name and type) are **runtime** parameters —
  the caller supplies them on each call;
- every other parameter is a **dependency**, resolved once when the closure is built —
  [autowired](autowiring.md) by default, or set by hand.

So a single implementation can mix injected services with per-call input:

```php
use function Typhoon\Type\nullOrT;
use function Typhoon\Type\stringT;

$dic
    ->object(RedisCache::class)
    ->bind(objectT(Cache::class));

$lookup = $dic
    ->function(static fn(Cache $cache, string $key): ?string => $cache->get($key))
    ->closure(closureT(params: [stringT()], return: nullOrT(stringT())));
// Ref<\Closure(string): ?string>
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
    ->function(/* … */)
    ->closure(/* … */)
    ->arg('cache', $apcuCache); // wire the dependency explicitly instead of autowiring
```

## Implementations

The callable passed to `function()` can be any of:

- a plain `callable` — a closure, a function name, an invokable;
- a `[Ref<object>, 'method']` pair, to bind a method of another service as the callable;
- a `Ref<callable>` — another callable service.

```php
$controller = $dic->object(Controller::class);

$dic
    ->function([$controller, 'handle']) // call Controller::handle as the closure
    ->closure(closureT(params: [objectT(Request::class)], return: objectT(Response::class)));
```

A method can also be wired the other way around, from the object's own config, with `method()->closure()`:

```php
$dic
    ->object(Controller::class)
    ->method('handle')
    ->closure(closureT(params: [objectT(Request::class)], return: objectT(Response::class)));
```
