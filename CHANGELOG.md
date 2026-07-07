# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.3] - 2026-07-07

### Fixed

- A service registered through an autoconfiguration wrapper (`FunctionAutoconfig::closure()`,
  `MethodAutoconfig::register()`) now records its `declaredAt` at the user's call site instead of the internal
  wrapper, so error messages point at your code rather than the library.

## [0.5.2] - 2026-07-07

### Fixed

- A closure runtime parameter passed explicitly through `arg()` / `args()` is now forwarded as-is instead of being
  wrapped as an injected value.

## [0.5.1] - 2026-07-02

### Changed

- Raised the minimum PHP version to 8.4.12, which fixes
  [GH-19044](https://github.com/php/php-src/issues/19044) (protected properties not scoped according to their
  prototype).

## [0.5.0] - 2026-06-30

### Added

- `Dic::run(Module $module, callable $main)` — recommended entry point.
  Resolves the service the module returns, runs `$main` with it, then disposes the scope and the container even on
  throw.
- `Dic::build(Module $module)` — resolves a service without disposing anything; meant for tests and debugging.
- `Dic::apply(callable $configurator)` — calls the configurator on the **same** `Dic`, keeping its bindings and
  autoconfiguration listeners in scope.
  A lightweight way to split a large `configure()` into smaller functions without changing the scope.
- `Module` interface (`configure(Dic): mixed`) — modules are now typed classes instead of plain callables.
- `Dic::provider($ref)` — registers a lazy `\Closure(): T` handle that resolves the wrapped service on each call.
- `Dic::onObject(callable(ObjectAutoconfig): void)` and
  `Dic::onFunction(callable(FunctionAutoconfig): void)` — autoconfiguration hooks.
  Each listener visits every `object()` / `function()` service declared on the same `Dic`;
  a submodule pulled in with `import()` runs its own isolated set of listeners.
- `Dic::object()` now accepts an optional factory: `object(string $class, null|callable|array|Ref $factory = null)`.
- `ObjectConfig::call(string $method, array $args, variadic)` — call a method on the object after construction
  (setter / side-effect).
- `ObjectConfig::chain(string $method, array $args, variadic)` — call a wither-style method and keep
  the returned instance.
- `ObjectConfig::variadic(iterable|Ref $variadic)` — pass a variadic argument to the constructor.
- `ObjectConfig::eager()` — explicitly disable lazy instantiation (counterpart to `lazy()`).
- `ObjectConfig::canBeScoped()` — adaptive lifetime: scoped if any transitive dependency is scoped,
  singleton otherwise.
- `ObjectConfig::method(string $name)` — expose a method as a `FunctionConfig` service.
- `ObjectConfig::doNotAutoconfigure()` and `FunctionConfig::doNotAutoconfigure()` — opt a service out of all
  `onObject()` / `onFunction()` listeners.
- `FunctionConfig::closure(ClosureT $type)` — turn a callable service into a typed `\Closure` service whose
  dependencies the container resolves.
  Dependencies are autowired; parameters declared in the `ClosureT` type are passed by the caller at call time.
- `#[Autowire]` parameter attribute, with the `autowire()` helper and `autowire` constant, to override the binding
  qualifier for a single parameter.
- New methods on the `Config` base (shared by all configurators): `bind(Type, qualifier)`, `tag(Tag)`,
  `disposer(callable)`.
- `Thesis\Dic\DisposalFailed` — thrown after teardown when one or more disposers fail.
  Disposal is best-effort: every disposer still runs, all failures are collected in `DisposalFailed::$errors`,
  and the throwable that triggered teardown (if any) is preserved as `getPrevious()`.

### Changed

- **BC break:** Namespace and facade renamed `Thesis\DIC` → `Thesis\Dic` (class `DIC` → `Dic`).
- **BC break:** Configurators moved and renamed `Thesis\DIC\Configurator\*` → `Thesis\Dic\Configuration\*Config`:
  `Value` → `ValueConfig`, `Obj` → `ObjectConfig`, `ScopedOf` → `ScopedConfig`,
  `TaggedList` → `TaggedListConfig`.
  `Func` and `Method` are unified into `FunctionConfig` (callable service);
  the typed-closure concept from `Func` / `Method` is now `ClosureConfig`,
  obtained via `FunctionConfig::closure(ClosureT)`.
- **BC break:** `DIC::init()` replaced by `Dic::run()` and `Dic::build()`.
- **BC break:** `DIC::require(callable)` renamed to `Dic::import(Module)`.
  The argument is now a `Module` instance instead of a plain callable;
  the submodule's bindings and autoconfiguration listeners are isolated from the caller's scope.
- **BC break:** `DIC::function()` now returns `FunctionConfig` — a callable-as-service ref.
  To get a typed `\Closure` service (the old `Func` behaviour), chain `->closure(ClosureT $type)` onto it.
- **BC break:** `DIC::scopedOf()` renamed to `Dic::scoped()`.
- **BC break:** `DIC::onResolveTags()` renamed to `Dic::onTagResolution()`;
  the callback parameter type `Tags` renamed to `TaggedRefs`.
- **BC break:** Lifetime `transient` replaced by `canBeScoped`; the `Lifetime` enum is now internal
  (configure via `singleton()` / `scoped()` / `canBeScoped()`).
- **BC break:** Qualifier mapping moved from the `#[Qualifier]` parameter attribute to `#[Autowire(qualifier: …)]`
  (or the `autowire(qualifier: …)` helper).
- **BC break:** `Mapping\DoNotAutowire` → `Thesis\Dic\DoNotAutowire`.
- **BC break:** Union types are autowired as a single composite type (`A|B`) instead of matching each member
  individually.
  Intersection types (`A&B`) are now autowirable too.

### Removed

- **BC break:** `DIC::inheritAutowiring()`.
- **BC break:** Instance-level `DIC::bind()` and `DIC::tag()` — bind via `Config::bind()`, tag via `Config::tag()`.
- **BC break:** Top-level `DIC::call()` — use `Dic::function()` to register a callable service,
  or `Dic::object(class, factory)` to register the object a factory produces.
- **BC break:** The `Thesis\DIC\Mapping` namespace, including the `#[Singleton]`, `#[Scoped]`, `#[Transient]`
  and `#[Qualifier]` attributes.
- **BC break:** Public `Thesis\DIC\Lifetime` enum (now internal).

## [0.4.0] - 2026-04-23

### Added

- `DIC::function()` now supports `#[Tag]` attributes on the function.
- `DIC::function()` now supports `#[Singleton]` and `#[Transient]` lifetime attributes on the function
  (defaults to `scoped`).

### Changed

- **BC break:** Move `Thesis\DIC\Mapping\doNotAutowire` constant to `Thesis\DIC\doNotAutowire`.
- **BC break:** Rename `Thesis\DIC\Scope` to `Scoped`.
- **BC break:** Rename `Thesis\DIC\Configurator\Scope` to `ScopedOf`.
- **BC break:** Rename `Scope::obtain()` to `resolve()`.
- **BC break:** Rename `DIC::scope()` to `DIC::scopedOf()`.
- **BC break:** Merge `DIC::bind()` and `bindQualifier()` into single
  `DIC::bind(mixed $value, Type $type, string|\Stringable|\UnitEnum $qualifier = '')`.
- **BC break:** Autowiring now matches bindings by exact type only.
- **BC break:** `DIC::bind()` and `Scoped::with()` now override previous bindings.

### Removed

- **BC break:** `DIC::install()` — use `DIC::init()`.
- **BC break:** Constants `singleton`, `scoped`, `transient` — use `Lifetime::*` directly.
- **BC break:** `Scope::$value` property — use `Scoped::resolve()`.
