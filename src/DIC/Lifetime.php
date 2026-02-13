<?php

declare(strict_types=1);

namespace Thesis\DIC;

/**
 * @api
 */
enum Lifetime
{
    case Singleton;
    case Scoped;
    case Transient;
}

/**
 * @api
 */
const singleton = Lifetime::Singleton;

/**
 * @api
 */
const scoped = Lifetime::Scoped;

/**
 * @api
 */
const transient = Lifetime::Transient;
