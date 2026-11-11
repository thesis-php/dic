<?php

declare(strict_types=1);

/**
 * Creates a singleton from a fiber started and resumed by hand, without ever running the Revolt loop,
 * and asks for it from the main context while that creation is suspended.
 * Must run in its own process: once a process awaits through Revolt, its loop stays running.
 */

use Thesis\Dic;

require __DIR__ . '/../../../vendor/autoload.php';

$provide = Dic::build(
    static fn(Dic $dic) => $dic->provider(
        $dic->object(stdClass::class, static function (): stdClass {
            if (Fiber::getCurrent() !== null) {
                Fiber::suspend();
            }

            return new stdClass();
        }),
    ),
);

$fiber = new Fiber($provide);
$fiber->start();
$concurrent = $provide();
$fiber->resume();
$created = $fiber->getReturn();

echo json_encode([
    'shared' => $created === $concurrent,
    'stored' => $provide() === $concurrent,
], JSON_THROW_ON_ERROR);
