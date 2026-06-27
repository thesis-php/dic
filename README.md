# Thesis Dic

A fresh take on the PHP dependency injection container, with all the features you expect.

- **Modular** — isolated modules, no global scope
- **Type-safe** with local reasoning
- **Autowiring** at the module level
- **Autoconfiguration** — plug in your attributes or autoconfigure by type
- **Tags** with flexible resolution
- **Scoped** service lifetimes
- **Callable** services (closures, methods, …)
- **Variadic parameters** supported

## Contents

- [Installation](#installation)
- [Quick start](#quick-start)
- [Documentation](#documentation)

## Installation

```shell
composer require thesis/dic
```

## Quick start

> This guide covers only a fraction of what Dic can do — just enough to get you started.

### Dic

The [`Thesis\Dic`](src/Dic.php) class is the heart of container configuration.
It lets you declare services, require modules, subscribe to events and more.

### Module

A module is the unit of composition: you assemble your application from modules,
and the application itself is just the root module.

A module is a `callable` that accepts `Dic` and returns whatever it exports.

### Ref

No string identifiers to invent — they aren't type-safe.
Instead, declaring a service returns a [`Thesis\Dic\Ref<T>`](src/Dic/Ref.php), its handle and identifier,
with `T` inferred from configuration:

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
See [Arguments](docs/arguments.md) for named, positional and variadic arguments.

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

To use a module inside another one, call `require()` and get whatever that module exports.
See [Modularity](docs/modularity.md) for how `require` isolates modules and when you might share autowiring instead.

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

To run an application, pass the root module to `Dic::run()` together with `$main` —
a function that receives the resolved service.
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

## Documentation

- [Objects](docs/object.md) — declaring object services, factories, post-construction calls, lazy instantiation
- [Values](docs/value.md) — declaring ready-made values and refs as services
- [Closures](docs/closure.md) — type-safe closure services, runtime parameters and dependencies
- [Arguments](docs/arguments.md) — named, positional and variadic arguments
- [Autowiring](docs/autowiring.md) — binding services to types and qualifiers
- [Tags](docs/tags.md) — tagging, collecting tagged services, tag resolution and autoconfiguration
- [Modularity](docs/modularity.md) — composing modules with `require`, and when to share autowiring
- [Lifetimes](docs/lifetime.md) — singleton, scoped and canBeScoped lifetimes, and the `Scoped<T>` handle
- [Disposal](docs/disposal.md) — releasing resources when a scope or the container is disposed
