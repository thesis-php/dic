<?php

declare(strict_types=1);

namespace Thesis\Dic;

use Thesis\Dic\Internal\NonCopyable;

/**
 * @api
 *
 * @template-covariant T
 * @template-covariant TTag of Tag<T>
 */
final readonly class TaggedRef
{
    use NonCopyable;

    /**
     * @param Ref<T> $ref
     * @param TTag $tag
     */
    public function __construct(
        public Ref $ref,
        public Tag $tag,
    ) {}
}
