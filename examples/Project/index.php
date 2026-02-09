<?php

declare(strict_types=1);

namespace Project;

use Thesis\DIC;

require_once __DIR__ . '/../../vendor/autoload.php';

$server = DIC::install(new App());
$server->run();
