<?php

declare(strict_types=1);

namespace Thesis\Dic\Error;

use Thesis\Dic\Ref;
use Thesis\Dic\Tag;

/**
 * @api
 */
final class TaggedAfterResolution extends ConfigurationError
{
    /**
     * @template T
     * @param Ref<T> $ref
     * @param Tag<T> $tag
     */
    public function __construct(Ref $ref, Tag $tag)
    {
        parent::__construct(\sprintf(
            <<<'TEXT'
                Cannot tag %s with %s: tags cannot be added once tag resolution has started.

                This usually means %1$s was registered during tag resolution (e.g. from an onResolution listener) and then matched an autoconfigurator that tags it. Exclude such services from autoconfiguration.
                TEXT,
            $ref,
            $tag::class,
        ));
    }
}
