# Modularity

You compose an application from modules.
A module is a class implementing the `Module<T>` interface:

```php
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;
use function Typhoon\Type\objectT;

/**
 * @implements Module<Ref<Cache>>
 */
final readonly class CacheModule implements Module
{
    public function configure(Dic $dic): mixed
    {
        return $dic
            ->object(RedisCache::class)
            ->bind(objectT(Cache::class));
    }
}
```

The root module is the one you hand to `Dic::run()` or `Dic::build()`.

## `import()`: isolated bindings

`import()` runs a module in a **fresh** `Dic` of its own, then hands you back whatever it returns:

```php
/**
 * @implements Module<Ref<Application>>
 */
final readonly class AppModule implements Module
{
    public function configure(Dic $dic): mixed
    {
        $cache = $dic->import(new CacheModule()); // Ref<Cache>

        return $dic
            ->object(ProductRepository::class)
            ->arg('cache', $cache);
    }
}
```

The submodule's bindings live in that fresh `Dic`, so they neither leak out nor see yours.
An imported module is a black box: you wire it in only through the `Ref`s it exports and the `Ref`s you pass in —
never through a shared type binding.
That is why `AppModule` injects the exported `$cache` explicitly instead of autowiring `Cache`:
`CacheModule`'s binding is invisible here.

[Autoconfiguration](tags.md) is scoped the same way: an `onObject()` / `onFunction()` listener registered inside a
module visits only that module's services, and an imported module's listeners never touch yours.

Isolation is only about configuration — the binding table and the `Dic` surface.
The underlying container is still shared, so every service across every module is built once, in one container.

For third-party modules, isolation is not just useful — it is the only safe option.
You do not control a vendor's bindings, and isolation guarantees their autowiring choices never collide with yours,
nor accidentally satisfy one of your parameters.

## `apply()`: shared scope

`apply()` calls a function on the **same** `$dic`, so its bindings and autoconfiguration listeners stay visible:

```php
final readonly class AppModule implements Module
{
    public function configure(Dic $dic): mixed
    {
        $dic->apply($this->registerCache(...));

        return $dic->object(ProductRepository::class); // autowires Cache from registerCache()
    }

    private function registerCache(Dic $dic): void
    {
        $dic
            ->object(RedisCache::class)
            ->bind(objectT(Cache::class));
    }
}
```

`apply()` is a lightweight way to split a large `configure()` into smaller functions without changing the scope.
A direct call `$this->registerCache($dic)` does the same thing; `apply()` only names the intent.

Because the scope is shared, a type bound in one function is autowirable in any of the others, and an `onObject()` /
`onFunction()` convention registered via `apply()` applies to every service on that `$dic`.

The flip side: a binding added in one applied function can silently change how another resolves a type.
Use `apply()` when the functions are tightly coupled parts of the same module;
use `import()` when the boundary matters.
