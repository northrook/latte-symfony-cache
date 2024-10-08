<?php

declare( strict_types = 1 );

namespace Northrook\Latte\Extension;

use Latte;
use Northrook\Latte\CacheRuntime;
use Northrook\Latte\Nodes\CacheNode;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Integrates {@see CacheInterface} into the {@see Latte\Engine} using a {@see Latte\Compiler\Tag}.
 */
final class CacheExtension extends Latte\Extension
{
    public function __construct(
        private readonly ?CacheInterface  $cacheInterface,
        private readonly ?LoggerInterface $logger = null,
        private readonly string           $tagName = 'cache',
    ) {}

    public function getTags() : array {
        return [ $this->tagName => [ CacheNode::class, 'create' ] ];
    }

    /**
     * Add to the {@see CacheRuntime} to the `$this->global` Latte variable.
     */
    public function getProviders() : array {
        return [ 'cacheRuntime' => new CacheRuntime( $this->cacheInterface, $this->logger ) ];
    }
}