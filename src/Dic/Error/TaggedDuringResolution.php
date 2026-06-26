<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Error;
use Thesis\Dic\Ref;
use Thesis\Dic\Tag;

/**
 * @api
 */
final class TaggedDuringResolution extends Error
{
    /**
     * @template T
     * @param Ref<T> $ref
     * @param Tag<T> $tag
     */
    public function __construct(Ref $ref, Tag $tag)
    {
        parent::__construct(\sprintf(
            'Cannot tag %s with "%s" during tag resolution',
            $ref,
            $tag::class,
        ));
    }
}
