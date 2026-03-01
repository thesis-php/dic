<?php

declare(strict_types=1);

namespace Thesis\DIC\Configurator;

use Thesis\DIC\Internal\Container;
use Thesis\DIC\Internal\Container\ServiceRegistrar;
use Thesis\DIC\Internal\Container\Subscriber;
use Thesis\DIC\Internal\Tagger;
use Thesis\DIC\Location;
use Thesis\DIC\Ref;
use Thesis\DIC\Tag;
use Thesis\DIC\TaggedRef;
use Thesis\DIC\Tags;
use function Typhoon\Formatter\format;

/**
 * @api
 *
 * @template T
 * @template TTag of Tag<T>
 * @implements Ref<list<T>>
 */
final class TaggedList implements Ref
{
    use HasDescription;
    use HasLifetime;

    /**
     * @use HasTags<list<T>>
     */
    use HasTags;

    /**
     * @internal
     *
     * @param class-string<TTag>|TTag $tag
     * @param ?callable(TaggedRef<T, TTag>, TaggedRef<T, TTag>): (-1|0|1) $sort
     */
    public function __construct(
        string|Tag $tag,
        ?callable $sort,
        Location $declaredAt,
        Subscriber $subscriber,
        Tagger $tagger,
    ) {
        $this->tagger = $tagger;
        $this->description = \sprintf('[tagged by %s at %s]', format($tag), $declaredAt);

        /** @var list<Ref<T>> */
        $refs = [];

        $subscriber->onResolveTags(
            static function (Tags $tags) use ($tag, $sort, &$refs): void {
                $taggedRefs = $tags->tagged($tag);

                if ($sort !== null) {
                    usort($taggedRefs, $sort);
                }

                $refs = array_column($taggedRefs, 'ref');
            },
        );

        $subscriber->onBeforeAssemble(
            function (ServiceRegistrar $registrar) use (&$refs): void {
                $registrar->register(
                    ref: $this,
                    factory: static fn(Container $container) => array_map($container->get(...), $refs),
                    lifetime: $this->lifetime,
                );
            },
        );
    }
}
