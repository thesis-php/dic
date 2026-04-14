<?php

declare(strict_types=1);

namespace Project;

use Project\Thesis\HttpServerModule\Server;
use Thesis\DIC;
use function Amp\trapSignal;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/Thesis/HttpServerModule/methods.php';

/** @var Server */
$httpServer = DIC::init(new App());

$httpServer->expose('0.0.0.0:1337');

$httpServer->start();

trapSignal([SIGINT, SIGTERM]);

$httpServer->stop();
