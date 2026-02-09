<?php

declare(strict_types=1);

namespace Project\HttpServer;

use Thesis\DIC\Tag;

/**
 * @phpstan-import-type Controller from Server
 * @implements Tag<Controller>
 */
#[\Attribute(\Attribute::TARGET_FUNCTION)]
final readonly class AsController implements Tag {}
