# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.0] - unreleased

### Added

- `Dic::run(callable $module, callable $function)` — recommended entry point. Resolves the service the module returns, runs `$function` with it, then disposes the scope and the container even on throw.
- `Dic::assemble(callable $module)` — resolves a service without disposing anything; meant for tests and debugging.
- `Dic::autoconfigure(callable $configurator)` — apply configuration to every service.
- `Dic::object()` now accepts an optional factory: `object(string $class, null|Ref|callable $factory = null)`.
- Typed error hierarchy under `Thesis\Dic\Error\*`: the root marker interface `Thesis\Dic\Error`, the phase bases `ConfigurationError` and `RuntimeError`, and concrete errors (`CircularDependency`, `SingletonDependsOnScoped`, `UnknownRef`, `CannotAutowire`, `UnsupportedBindingType`, `ConfigurationFrozen`, `TaggedAfterResolution`, `InvalidArgument`, …).
- `#[Autowire]` parameter attribute, with the `autowire()` helper and `autowire` constant, to set a binding qualifier per parameter.
- New configurator methods on the shared `Config` / `Autoconfig` base:
  - `bind(Type $type, qualifier)` — bind a service to a type.
  - `label(string $label)` — human-readable label.
  - `disposer(callable $disposer)` — register a disposer.
  - `canBeScoped()` lifetime.
- New `ObjectConfig` methods: `call()` (call a method after construction), `chain()` (call a method and use the returned instance), `variadic()`, `eager()`.

### Changed

- **BC break:** Namespace and facade renamed `Thesis\DIC` → `Thesis\Dic` (class `DIC` → `Dic`).
- **BC break:** Configurators moved and renamed `Thesis\DIC\Configurator\*` → `Thesis\Dic\Configuration\*Config`: `Value` → `ValueConfig`, `Obj` → `ObjectConfig`, `Func`/`Call` → `ClosureConfig`, `ScopedOf` → `ScopedConfig`, `TaggedList` → `TaggedListConfig`, `Method` → `MethodConfig`.
- **BC break:** `DIC::init()` replaced by `Dic::run()` and `Dic::assemble()`.
- **BC break:** `DIC::function()` renamed to `Dic::closure()` and now takes the closure `Type` explicitly.
- **BC break:** `DIC::scopedOf()` renamed to `Dic::scoped()`.
- **BC break:** `DIC::onResolveTags()` renamed to `Dic::onTagResolution()`.
- **BC break:** Lifetime `transient` replaced by `canBeScoped`; the `Lifetime` enum is now internal (configure via `singleton()` / `scoped()` / `canBeScoped()`).
- **BC break:** Qualifier mapping moved from the `#[Qualifier]` attribute to the `#[Autowire]` parameter attribute.
- **BC break:** `Mapping\DoNotAutowire` → `Thesis\Dic\DoNotAutowire`.
- **BC break:** Union types are now autowired as a single composite type (`A|B`) instead of matching each member individually. Intersection types (`A&B`) are now autowirable too.

### Removed

- **BC break:** `DIC::inheritAutowiring()`.
- **BC break:** Instance-level `DIC::bind()` and `DIC::tag()` — bind via `Config::bind()`, tag via `Config::tag()`.
- **BC break:** Top-level `DIC::call()` entry — use `Dic::closure()`.
- **BC break:** The `Thesis\DIC\Mapping` namespace, including the `#[Singleton]`, `#[Scoped]`, `#[Transient]` and `#[Qualifier]` attributes.
- **BC break:** Public `Thesis\DIC\Lifetime` enum (now internal).

## [0.4.0] - 2026-04-23

### Added

- `DIC::function()` now supports `#[Tag]` attributes on the function.
- `DIC::function()` now supports `#[Singleton]` and `#[Transient]` lifetime attributes on the function (defaults to `scoped`).

### Changed

- **BC break:** Move `Thesis\DIC\Mapping\doNotAutowire` constant to `Thesis\DIC\doNotAutowire`.
- **BC break:** Rename `Thesis\DIC\Scope` to `Scoped`.
- **BC break:** Rename `Thesis\DIC\Configurator\Scope` to `ScopedOf`.
- **BC break:** Rename `Scope::obtain()` to `resolve()`.
- **BC break:** Rename `DIC::scope()` to `DIC::scopedOf()`.
- **BC break:** Merge `DIC::bind()` and `bindQualifier()` into single `DIC::bind(mixed $value, Type $type, string|\Stringable|\UnitEnum $qualifier = '')`.
- **BC break:** Autowiring now matches bindings by exact type only.
- **BC break:** `DIC::bind()` and `Scoped::with()` now override previous bindings.

### Removed

- **BC break:** `DIC::install()` — use `DIC::init()`.
- **BC break:** Constants `singleton`, `scoped`, `transient` — use `Lifetime::*` directly.
- **BC break:** `Scope::$value` property — use `Scoped::resolve()`.
