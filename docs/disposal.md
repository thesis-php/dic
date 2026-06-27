# Disposal

`disposer()` registers a callback that runs when the service's owning container or scope is torn down — to close connections, flush buffers, release handles.

```php
$dic
    ->object(Connection::class)
    ->disposer(static fn(Connection $connection) => $connection->close());
```

The callback receives the instance and the error in flight, if any:

```php
$dic
    ->object(Transaction::class)
    ->disposer(static function (Transaction $tx, ?\Throwable $error): void {
        if ($error === null) {
            $tx->commit();
        } else {
            $tx->rollback();
        }
    });
```

A `null` error means a clean teardown; a non-`null` error is the throwable that caused it, so a disposer can distinguish success from failure.

## When disposers run

Disposal follows the [lifetime](lifetime.md):

- a scoped instance is disposed when its scope is disposed;
- a singleton is disposed when the container is disposed.

Only instances that were actually created are disposed.
A service never built — for example a [lazy](object.md) one that was never used — has no disposer call.

## Multiple disposers

A service may register several disposers; they run in registration order.

## When a disposer throws

Disposal is best-effort: every disposer still runs even if another throws, and both the scope and the container are always disposed.

Failures are collected and surfaced after teardown as a `Thesis\Dic\DisposalFailed`, whose `errors` property holds every throwable raised by a disposer:

```php
try {
    Dic::run($module, $main);
} catch (DisposalFailed $failed) {
    foreach ($failed->errors as $error) {
        $logger->error('Disposer failed', ['exception' => $error]);
    }
}
```

If teardown was triggered by an error — `$main` threw, or a disposer ran with a non-`null` `$error` — that original error is not masked: it becomes the `DisposalFailed`'s `getPrevious()`, while the disposer failures stay in `errors`.

You still want disposers to be reliable, but a throwing one no longer aborts the cleanup of everything else.
