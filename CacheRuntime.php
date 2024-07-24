<?php

namespace Northrook\Latte;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @internal
 */
final readonly class CacheRuntime
{
    public function __construct(
        private ?CacheInterface  $cache = null,
        private ?LoggerInterface $logger = null,
    ) {}

    public function get( string $assetId, bool $useCache, callable $callback = null ) : string {

        if ( false === $useCache ) {
            return $callback( null );
        }

        try {
            return $this->cache?->get( $assetId, $callback );
        }
        catch ( \Throwable $exception ) {
            $this->logger?->error(
                "Exception thrown when using {runtime}: {message}.",
                [
                    'runtime'   => $this::class,
                    'message'   => $exception->getMessage(),
                    'exception' => $exception,
                ],
            );
            return $callback( null );
        }
    }
}