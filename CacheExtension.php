<?php

declare( strict_types = 1 );

namespace Northrook\Latte;

use Latte;
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
    ) {}

    public function getTags() : array {
        return [ 'cached' => [ CacheNode::class, 'create' ] ];
    }

    /**
     * Add to the {@see CacheRuntime} to the `$this->global` Latte variable.
     */
    public function getProviders() : array {
        return [ 'cacheRuntime' => new CacheRuntime( $this->cacheInterface, $this->logger ) ];
    }
}