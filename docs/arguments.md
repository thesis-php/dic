# Arguments

`object()`, `closure()`, `call()` and `chain()` all take arguments the same way.

By default Dic autowires non-variadic parameters.
When autowiring can't or shouldn't decide, override individual arguments by name or position.

## Setting arguments

Use `arg()` to set a single argument, or `args()` to set several at once:

```php
$dic
    ->object(Mailer::class)
    ->arg('transport', $transport)
    ->args([
        'from' => 'no-reply@example.com',
        'retries' => 5,
    ]);
```

`args()` only replaces the entries you pass — like `array_replace`, not a full reset.
Calling `arg()` / `args()` repeatedly is fine; for each parameter the last value wins:

```php
$dic
    ->object(Mailer::class)
    ->arg('transport', $smtpTransport)
    ->arg('transport', $sesTransport); // $sesTransport is used
```

Referencing a parameter that doesn't exist throws an error.

## Values

A value may be a plain value or a `Ref<T>` to another service.
Array arguments can mix the two — refs are resolved recursively, at any depth:

```php
$dic
    ->object(NotificationCenter::class)
    ->arg('channels', [
        'email' => $emailChannel, // Ref
        'sms' => $smsChannel,     // Ref
        'fallback' => false,      // plain value
    ]);
```

## Variadic parameters

Given a variadic signature:

```php
final readonly class Logger
{
    /**
     * @var list<LogHandler>
     */
    public array $handlers;

    public function __construct(LogHandler ...$handlers)
    {
        $this->handlers = $handlers;
    }
}
```

`arg()` / `args()` feed the variadic one element at a time.
As in PHP itself, a variadic function accepts extra positions and names, so they never error on an unknown key here — the value is appended instead:

```php
$dic
    ->object(Logger::class)
    ->arg(0, $stderrHandler)
    ->arg(1, $fileHandler)
    ->arg('syslog', $syslogHandler);

// $handlers === [$stderrHandler, $fileHandler, 'syslog' => $syslogHandler]
```

Also as in PHP, passing the variadic parameter's own name adds a *keyed* element rather than replacing the whole variadic:

```php
$dic
    ->object(Logger::class)
    ->arg('handlers', $fileHandler); // ['handlers' => $fileHandler], a single keyed element
```

A positional element after a named one is rejected — keep positional entries first.

To set the whole variadic at once, use `variadic()`:

```php
$dic
    ->object(Logger::class)
    ->variadic([$stderrHandler, $fileHandler]);
```

It also accepts a `Ref` to an iterable, which is unpacked into the parameter.
This pairs naturally with `taggedList()`:

```php
$dic
    ->object(Logger::class)
    ->variadic($dic->taggedList(LogHandlerTag::class));
```

## Deferred configuration

A config stays mutable until the container is built, so arguments can be set later — for example inside an `onTagResolution()` listener or during `autoconfigure()`:

```php
$logger = $dic->object(Logger::class);

$dic->onTagResolution(static function (TaggedRefs $tags) use ($logger): void {
    $logger->variadic(array_map(
        static fn(TaggedRef $tagged) => $tagged->ref,
        $tags->find(LogHandlerTag::class),
    ));
});
```
