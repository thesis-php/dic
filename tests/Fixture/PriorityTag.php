<?php

declare(strict_types=1);

namespace Thesis\Fixture;

use Thesis\Dic\Tag;

/**
 * @implements Tag<Cache>
 */
final readonly class PriorityTag implements Tag
{
    public function __construct(
        public int $priority,
    ) {}
}
