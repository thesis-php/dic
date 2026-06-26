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

- The root base is the abstract class `Thesis\Dic\Error extends \LogicException` — it deliberately shares its name with the `Error\*` namespace (legal in PHP; reference it from inside that namespace via `use Thesis\Dic\Error;`). Catch it to handle any container error. All errors are build-time: the full dependency graph is validated eagerly at build, so resolving from a built container raises no DIC-specific error — there is no marker interface and no runtime-error phase.
- Every concrete error lives under `Error\*` and `extends Thesis\Dic\Error`.
- **Never throw a message-less exception.** User-triggerable problems → a typed `@api` error under `Error/` that takes structured context (ref / parameter / class / reason) and builds its own message — not a raw string (e.g. `CannotAutowire(Parameter $parameter, string $reason)`). Internal "can't happen" invariants → `Internal\ShouldNotHappen('reason')` (always a reason; not part of the public hierarchy; the builder's service resolution rethrows it as-is and never wraps it in `InvalidConfigurationError`).
- PHPDoc on errors: `@api` always; prose only when it adds something beyond the class name (markers get prose; self-explanatory leaves get just `@api`).

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
