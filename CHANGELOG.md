# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.4.0] - 2026-04-22

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
