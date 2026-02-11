<?php

declare(strict_types=1);

namespace Project;

use Project\HttpServer\Request;
use Thesis\DIC;

require_once __DIR__ . '/../../vendor/autoload.php';

$server = DIC::install(new App()); // @phpstan-ignore argument.type

$server->handle(new Request('/authenticate'));
