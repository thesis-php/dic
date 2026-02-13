<?php

declare(strict_types=1);

namespace Project;

use Amp\Http\Server\DefaultErrorHandler;
use Thesis\DIC;
use function Amp\trapSignal;

require_once __DIR__ . '/../../vendor/autoload.php';

[$server, $router] = DIC::install(new App());

$server->expose('0.0.0.0:1337');

$server->start($router, new DefaultErrorHandler());

trapSignal([SIGINT, SIGTERM]);

$server->stop();
