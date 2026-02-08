<?php

declare(strict_types=1);

namespace Thesis\DIC\Internal;

use Thesis\DIC\Tag;

/**
 * @internal
 *
 * @template T
 * @extends Resolvable<list<T>, TaggedList\Data<T>>
 */
final class TaggedList extends Resolvable
{
    /**
     * @param class-string<Tag<T>>|Tag<T> $tag
     */
    public function __construct(
        public string|Tag $tag,
    ) {
        parent::__construct(new TaggedList\Data($tag));
    }

    public function register(Autowiring $autowiring, Tags $tags): void {}

    protected function toFactory(Autowiring $autowiring, Tags $tags): \Closure
    {
        $services = [];

        if (\is_string($this->data->tag)) {
            foreach ($tags as $serviceTag) {
                if ($serviceTag->tag instanceof $this->data->tag) {
                    $services[] = $serviceTag->service;
                }
            }
        } else {
            foreach ($tags as $serviceTag) {
                if ($serviceTag->tag === $this->data->tag) {
                    $services[] = $serviceTag->service;
                }
            }
        }

        return static fn() => array_column($services, 'value');
    }
}
