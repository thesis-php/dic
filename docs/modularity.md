# Modularity

You assemble an application from **modules**, and the application itself is just the root module.
A module is a `callable` that accepts a `Dic` and returns whatever it exports — usually a `Ref<T>`:

```php
use Thesis\Dic;
use Thesis\Dic\Ref;
use function Typhoon\Type\objectT;

/**
 * @return Ref<Cache>
 */
function cacheModule(Dic $dic): Ref
{
    return $dic
        ->object(RedisCache::class)
        ->bind(objectT(Cache::class));
}
```

The root module is the one you hand to `Dic::run()` or `Dic::assemble()`;
every other module is pulled in from inside another one.
There are two ways to do that, and they differ in one thing: whether [autowiring](autowiring.md) is shared.

## `require()`: isolated bindings (recommended)

`require()` runs a module in a **fresh** `Dic` of its own, then hands you back whatever it returns:

```php
function appModule(Dic $dic): Ref
{
    $cache = $dic->require(cacheModule(...)); // Ref<Cache>

    return $dic
        ->object(ProductRepository::class)
        ->arg('cache', $cache);
}
```

The submodule's bindings live in that fresh `Dic`, so they neither leak out nor see yours.
A required module is a black box: you wire it in only through the `Ref`s it exports and the `Ref`s you pass in —
never through a shared type binding.
That is why `appModule` injects the exported `$cache` explicitly instead of autowiring `Cache`:
`cacheModule`'s binding is invisible here.

Isolation is only about configuration — the binding table and the `Dic` surface.
The underlying container is still shared, so every service across every module is built once, in one container.

This is the recommended way to compose modules: each one reasons about its own autowiring,
and adding a binding in one module can never silently change how another resolves a type.

## Vendor modules: always `require()`

For third-party modules, `require()` is not just a recommendation — it is the only safe option.
You do not control a vendor's bindings, and isolation guarantees their autowiring choices never collide with yours,
nor accidentally satisfy one of your parameters.
Calling a vendor module directly would merge two codebases into one binding table — fragile and surprising.

## Sharing autowiring across your own modules

A module is an ordinary function, so within your own project you can skip `require()`
and just call it with the **same** `$dic`:

```php
function appModule(Dic $dic): Ref
{
    cacheModule($dic); // same $dic — the Cache binding is now visible here

    return $dic->object(ProductRepository::class); // its Cache parameter autowires to RedisCache
}
```

Now the modules share one autowiring table: a type bound in one is autowirable in any of the others,
so you can split a project into functions and let bindings flow between them without exporting every `Ref`.
This brings the container closer to the conventional, global-scope style of Symfony, Laravel and the like,
where every binding lives in one shared registry.

This is a deliberate trade-off, **not** the default we recommend.
Sharing makes autowiring effectively global across those modules,
so the local reasoning that [autowiring](autowiring.md) is built around no longer holds:
a binding added in one place can change resolution somewhere far away.
Prefer `require()`; reach for a shared `$dic` only for a few tightly-coupled internal modules
where you genuinely want them to live in one autowiring scope.
