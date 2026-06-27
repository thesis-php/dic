# CLAUDE.md

A small, opinionated dependency-injection container (`Thesis\Dic`). PHP 8.4.

## Commands (everything runs in Docker via `make`)

- `make phpstan` — PHPStan, max level. Must stay clean.
- `make test` — test suite (Testo).
- `make fixer` / `make fixer-check` — PHP-CS-Fixer (apply / dry-run).
- `make check` — everything (fixer-check, rector, phpstan, test, …).
- `make run CMD='php …'` — one-off command in the php container.

After any source change run `make phpstan` and `make test`; before finishing, `make fixer`.

## Errors (`Thesis\Dic\BuildError`)

- `Thesis\Dic\BuildError extends \LogicException` is a single `final` class — the one public error type; catch it to handle any container error. There is no hierarchy: construct it only through its `@internal` named static factories (e.g. `BuildError::cannotAutowireNoCandidate($parameter)`, `BuildError::circularDependency(...)`), each carrying structured context (ref / parameter / class / reason) and building its own message. The private `cannotAutowire()` / `invalidSignatureArgument()` helpers hold the shared message prefix (the atom); named factories delegate to them. The constructor rewrites `file`/`line` to the throw site so the static-factory frame doesn't leak into `getFile()/getLine()`.
- The name says the phase: every error is raised at **build**, never while a built container resolves services. The full dependency graph is validated eagerly at build — no marker interface, no runtime-error phase.
- `Internal\Autowiring\UnsupportedBindingType` (a separate `@internal` `\LogicException`) is **not** a `BuildError`: it's an internal control-flow signal, caught by type during autowiring and converted into a `BuildError` (`cannotAutowireUnsupportedBindingType` / `unsupportedBindingType`), so it must stay outside the `BuildError` type to remain catchable.
- **Never throw a message-less exception.** User-triggerable problems → a named `BuildError::…()` factory (no raw strings at the throw site). Internal "can't happen" invariants → `Internal\ShouldNotHappen('reason')` (always a reason). `ShouldNotHappen` is a **sibling** of `BuildError` (both extend `\LogicException`), not a `BuildError`; service resolution wraps only `BuildError` into `BuildError::invalidServiceFactory($ref, …)`, so a `ShouldNotHappen` thrown while building a factory propagates raw rather than being mis-attributed to the user's config — the narrow `catch (BuildError)` gives this automatically, no explicit rethrow.
- PHPDoc: `@api` on the `BuildError` class; the `@internal` factories are self-documenting by name — prose only when it adds something beyond the name.

## Rendering (error messages)

- Dependency paths render as indented trees; an edge is `{path} → {node}`, and an empty `Dependency.path` renders as just the node (no `→`).
- Node labels render the **real type**, not prose: `Scoped<X>`, `\Closure(…): …` (via `Typhoon\Type\stringify`) — never placeholders.
- Injected values are wrapped in double quotes. A `Ref` quotes itself in `Ref::__toString` (`"{label}" ({location})`), so insert refs raw (`{$ref}`); for everything else use `sprintf` with `"%s"` rather than escaping quotes inside an interpolated string.

## Markdown

- One sentence per line: start each new sentence on its own line within the same paragraph (semantic line breaks). Blank line still separates paragraphs.
- Soft-wrap at 120 columns; only wrap a single sentence onto the next line if it exceeds that.

## Taste

- PHP 8.4 throughout: property hooks, asymmetric visibility (`public private(set)`), `readonly`.
- Put each format/convention in one place (the atom) rather than scattering string-building.
- Keep transient single-use accumulators mutable; reserve immutability for shared/aliased data.
- Method names must match behavior.
- A multiline `args([...])` array: one entry per line with a trailing comma; never pack a multiline args array onto one line.
- Method chains are always multiline: the receiver and each `->` call on its own line, even for a single chained call.
- Add an inverse method (e.g. `eager()` for `lazy()`) only when a global default exists that a per-service call must override; a plain per-service opt-out (like `doNotAutowire()`) needs no paired re-enabler.
