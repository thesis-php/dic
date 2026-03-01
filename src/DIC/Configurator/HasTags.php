<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Tag;

/**
 * @api
 *
 * @template-covariant T
 * @require-implements Ref<T>
 */
trait HasTags
{
    private readonly Tagger $tagger;

    /**
     * @param Tag<T> $tag
     */
    final public function tag(Tag $tag): static
    {
        $this->tagger->tag($this, $tag);

        return $this;
    }
}
