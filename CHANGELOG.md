# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.5.0] - unreleased

### Added

- `DIC\qualifier()` function.
- `Mapping\Attribute` interface for creating custom service attributes.
- `Mapping\TagAttribute` base class for tags that can be used as attributes.
- `$dic->obj($class)->call($method, $args)` — call a method on the object after construction.
- `$dic->obj($class)->chainCall($method, $args)` — call a method and use the returned instance.
- `$dic->obj($class)->methodFactory($method)` — use object's method as a service factory.

### Changed

- **BC break:** `DIC::init()` now returns `T` inferred from the `Ref<T>` returned by `$app`.
- **BC break:** `DIC::call()` renamed to `DIC::factory()`.
- **BC break:** PHP attributes on classes, methods, and functions are now scanned for `Mapping\Attribute` instead of `Tag`. Existing tag attributes must implement `Mapping\Attribute` (or extend `Mapping\TagAttribute`).
- **BC break:** Union types are not autowired anymore.

### Removed

- **BC break:** `DIC::inheritAutowiring()`.

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
