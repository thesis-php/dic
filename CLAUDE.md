# CLAUDE.md

A small, opinionated dependency-injection container (`Thesis\Dic`). PHP 8.4.

## Commands (everything runs in Docker via `make`)

- `make phpstan` — PHPStan, max level. Must stay clean.
- `make test` — test suite (Testo).
- `make fixer` / `make fixer-check` — PHP-CS-Fixer (apply / dry-run).
- `make check` — everything (fixer-check, rector, phpstan, test, …).
- `make run CMD='php …'` — one-off command in the php container.

After any source change run `make phpstan` and `make test`; before finishing, `make fixer`.

## Errors (namespace `Thesis\Dic\Error`)

- The root marker is the **interface** `Thesis\Dic\Error` — it deliberately shares its name with the `Error\*` namespace (legal in PHP; reference it from inside the namespace via `use Thesis\Dic\Error;`).
- Two abstract phase bases: `Error\ConfigurationError extends \LogicException` (build-time) and `Error\RuntimeError extends \RuntimeException` (resolution-time). Every concrete error `extends` one of them — do not `implements` the markers directly.
- **Never throw a message-less exception.** User-triggerable problems → a typed `@api` error under `Error/` with context (ref / parameter / class). Internal "can't happen" invariants → `Internal\ShouldNotHappen('reason')` (always a reason; not part of the public hierarchy; `Ref::resolve()` rethrows it as-is and never wraps it in `InvalidConfiguration`).
- PHPDoc on errors: `@api` always; prose only when it adds something beyond the class name (markers get prose; self-explanatory leaves get just `@api`).

## Rendering (error messages)

- Dependency paths render as indented trees; an edge is `{path} → {node}`, and an empty `Dependency.path` renders as just the node (no `→`).
- Node labels render the **real type**, not prose: `Scoped<X>`, `\Closure(…): …` (via `Typhoon\Type\stringify`) — never placeholders.

## Taste

- PHP 8.4 throughout: property hooks, asymmetric visibility (`public private(set)`), `readonly`.
- Put each format/convention in one place (the atom) rather than scattering string-building.
- Keep transient single-use accumulators mutable; reserve immutability for shared/aliased data.
- Method names must match behavior.
