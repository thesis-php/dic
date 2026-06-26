# Thesis Dic

A fresh take on the PHP dependency injection container, with all the features you expect.

- **Type-safe** — types are resolved by local reasoning, so a small plugin can cover what static analyzers can't.
- **Modular** — isolated modules, no global scope.
- **Autowiring** at the module level.
- **Autoconfiguration** via a flexible attribute.
- **Tags** with flexible resolution.
- **Scoped** service lifetimes.
- **Fully encapsulated** container.
- **Variadic parameters** supported.
- **Callable** services out of the box (closures, invokables, methods, …).
- Built for **long-running runtimes** (AMPHP, FrankenPHP, RoadRunner, Swoole, …).

## Installation

```shell
composer require thesis/dic
```

## Quick start

### Dic

The `Thesis\Dic` class is the heart of container configuration.
It lets you declare services, require modules, subscribe to events and more.

### Module

A module is the unit of composition: you assemble your application from modules, and the application itself is just the root module.

A module is a `callable` that accepts `Thesis\Dic` and returns whatever it exports.

### Ref

No string identifiers to invent — they aren't type-safe.
Instead, declaring a service returns a `Ref<T>`, its handle and identifier, with `T` inferred from configuration:

```php
$logger = $dic->object(NullLogger::class); // Ref<NullLogger>
```

A ref *is* the service's identity: a distinct ref is a distinct service.
Assign it to a variable and pass it around to inject, import or export.

### Declaring services

Use `object()` to declare an object service, and `arg()` to override individual constructor arguments:

```php
use Psr\Log\NullLogger;
use Thesis\Dic;

function consoleModule(Dic $dic): void
{
    $logger = $dic->object(NullLogger::class);

    $dic
        ->object(ConsoleApplication::class)
        ->arg('logger', $logger);
}
```

The `$logger` ref is passed as a constructor argument — that's how services get wired together.

### Putting it all together

A module can depend on services it doesn't declare itself: typehint them as `Ref<T>` and wire them in.

```php
use Psr\Log\LoggerInterface;
use Thesis\Dic;
use Thesis\Dic\Ref;

final readonly class ConsoleModule
{
    /**
     * @param Ref<LoggerInterface> $logger
     */
    public function __construct(
        private Ref $logger,
    ) {}

    /**
     * @return Ref<ConsoleApplication>
     */
    public function __invoke(Dic $dic): Ref
    {
        return $dic
            ->object(ConsoleApplication::class)
            ->arg('logger', $this->logger);
    }
}
```

To use a module inside another one, call `Dic::require($module)` and get whatever that module exports.

```php
use Psr\Log\NullLogger;
use Thesis\Dic;
use Thesis\Dic\Ref;

/**
 * @return Ref<ConsoleApplication>
 */
function myApp(Dic $dic): Ref
{
    $logger = $dic->object(NullLogger::class);

    $cli = $dic->require(new ConsoleModule($logger));

    return $cli;
}
```

To run an application, pass the root module to `Dic::run()` together with `$main` — a function that receives the resolved service.
The container builds, calls `$main`, and disposes everything afterwards — even on failure:

```php
use Thesis\Dic;

$status = Dic::run(
    module: myApp(...),
    main: static fn (ConsoleApplication $cli) => $cli->run(),
);

exit($status);
```

For tests and debugging, `Dic::assemble()` returns the resolved module's export without disposing anything:

```php
use Testo\Assert;
use Thesis\Dic;

$cli = Dic::assemble(myApp(...));

Assert::instanceOf($cli, ConsoleApplication::class);
```
