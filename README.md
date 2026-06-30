# Thesis Dic

A fresh take on the PHP dependency injection container, with all the features you expect.

- **Modular** — isolated or shared config scope per module
- **Type-safe** with local reasoning
- **Autowiring** at the module level
- **Autoconfiguration** — plug in your attributes or autoconfigure by type
- **Tags** with flexible resolution
- **Lifetimes** — singleton / canBeScoped / scoped
- **Callable** services — functions and methods as first-class services
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

A module is the unit of composition: you compose your application from modules,
and the application itself is just the root module.

A module is a class implementing [`Thesis\Dic\Module`](src/Dic/Module.php):
it receives a `Dic`, declares services, and returns whatever it exports — usually a `Ref<T>`.

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
use Thesis\Dic\Module;

final readonly class ConsoleModule implements Module
{
    public function configure(Dic $dic)
    {
        $logger = $dic->object(NullLogger::class);
    
        $dic
            ->object(ConsoleApplication::class)
            ->arg('logger', $logger);
    }   
}
```

The `$logger` ref is passed as a constructor argument — that's how services get wired together.
See [Arguments](docs/arguments.md) for named, positional and variadic arguments.

### Putting it all together

A module can depend on services it doesn't declare itself: accept them as constructor arguments and wire them in.

```php
use Psr\Log\LoggerInterface;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<ConsoleApplication>>
 */
final readonly class ConsoleModule implements Module
{
    /**
     * @param Ref<LoggerInterface> $logger
     */
    public function __construct(
        private Ref $logger,
    ) {}

    public function configure(Dic $dic): mixed
    {
        return $dic
            ->object(ConsoleApplication::class)
            ->arg('logger', $this->logger);
    }
}
```

To use a module inside another one, call `import()` and get whatever that module exports.
See [Modularity](docs/modularity.md) for how `import()` isolates modules and when to use `apply()` instead.

```php
use Psr\Log\NullLogger;
use Thesis\Dic;
use Thesis\Dic\Module;
use Thesis\Dic\Ref;

/**
 * @implements Module<Ref<ConsoleApplication>>
 */
final readonly class MyApp implements Module
{
    public function configure(Dic $dic): mixed
    {
        $logger = $dic->object(NullLogger::class);

        return $dic->import(new ConsoleModule($logger));
    }
}
```

To run an application, pass the root module to `Dic::run()` together with `$main` —
a function that receives the resolved service.
The container builds, calls `$main`, and disposes everything afterwards — even on failure:

```php
use Thesis\Dic;

$status = Dic::run(
    module: new MyApp(),
    main: static fn (ConsoleApplication $cli) => $cli->run(),
);

exit($status);
```

For tests and debugging, `Dic::build()` returns the resolved module's export without disposing anything:

```php
use Testo\Assert;
use Thesis\Dic;

$cli = Dic::build(new MyApp());

Assert::instanceOf($cli, ConsoleApplication::class);
```

## Documentation

- [Objects](docs/object.md) — declaring object services, factories, post-construction calls, lazy instantiation
- [Values](docs/value.md) — declaring ready-made values and refs as services
- [Functions and closures](docs/closure.md) — type-safe callable services, runtime parameters and dependencies
- [Arguments](docs/arguments.md) — named, positional and variadic arguments
- [Autowiring](docs/autowiring.md) — binding services to types and qualifiers
- [Tags](docs/tags.md) — tagging, collecting tagged services, tag resolution and autoconfiguration
- [Modularity](docs/modularity.md) — `Module` interface, `import()` and `apply()`
- [Lifetimes](docs/lifetime.md) — singleton, scoped and canBeScoped lifetimes, and the `Scoped<T>` handle
- [Disposal](docs/disposal.md) — releasing resources when a scope or the container is disposed
