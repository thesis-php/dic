# Objects

`object()` declares a class as a service and returns its `Ref<T>`.
Constructor parameters are autowired, so the common case is a single call:

```php
$mailer = $dic->object(Mailer::class); // Ref<Mailer>
```

See:

- `arg()` / `args()` / `variadic()` — [Arguments](arguments.md)
- `singleton()` / `scoped()` / `canBeScoped()` — [Lifetimes](lifetime.md)
- `bind()` — [Autowiring](autowiring.md)
- `disposer()` — [Disposal](disposal.md)

## Custom factory

Pass a factory as the second argument to build the instance yourself instead of calling the constructor.
The factory's own parameters are autowired:

```php
$dic->object(Mailer::class, Mailer::fromConfig(...));
```

The factory may also be a method of another service, declared with `method()`:

```php
$dic->object(
    class: Mailer::class,
    factory: $dic->object(MailerFactory::class)->method('create'),
);
```

## Post-construction calls

Use `call()` for a setter or any side-effecting method invoked after construction:

```php
$dic
    ->object(Mailer::class)
    ->call('setLogger', args: [$logger]);
```

Use `chain()` when the method returns the value to keep, as with immutable (wither-style) objects:

```php
$dic
    ->object(Mailer::class)
    ->chain('withRetries', args: [5]);
```

Both accept the method's `args` and a `variadic`, just like declaring a service — see [Arguments](arguments.md):

```php
$dic
    ->object(Logger::class)
    ->call('pushHandlers', variadic: [$stderrHandler, $fileHandler]);
```

## Lazy instantiation

`lazy()` defers construction until the service is first used, returning a lazy proxy in the meantime;
`eager()` undoes it:

```php
$dic
    ->object(Mailer::class)
    ->lazy();
```

This relies on PHP 8.4 [lazy objects](https://www.php.net/manual/en/language.oop5.lazy-objects.php),
so the class passed to `object()` must be instantiable and support them.
