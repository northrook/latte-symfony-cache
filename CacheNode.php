<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare( strict_types = 1 );

namespace Northrook\Latte;

use Latte\Compiler\NodeHelpers;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\Position;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;


/**
 * {cache} ... {/cache}
 */
class CacheNode extends StatementNode
{
    public ArrayNode $args;
    public AreaNode  $content;
    public ?Position $endLine;

    public ?string                    $cacheId  = null;
    public bool                       $useCache = true;
    public \DateInterval | int | null $expires  = null;

    /** @return \Generator<int, ?array, array{AreaNode, ?Tag}, static> */
    public static function create( Tag $tag ) : \Generator {
        $node       = $tag->node = new static();
        $node->args = $tag->parser->parseArguments();
        [ $node->content, $endTag ] = yield;
        $node->endLine = $endTag?->position;

        $node->parseCacheArguments();
        return $node;
    }

    public function parseCacheArguments() : void {

        foreach ( $this->args->toArguments() as $i => $argumentNode ) {

            $key   = (string) $argumentNode->name;
            $value = NodeHelpers::toValue( $argumentNode->value );

            if ( $i === 0 && ( !$key || $key === 'id' ) ) {
                $this->cacheId = \is_string( $value )
                    ? $this->normalizeKey( $value )
                    : $this->hashKey( $value );
                continue;
            }

            if ( \str_starts_with( $key, 'expire' ) ) {
                if ( \is_int( $value ) ) {
                    $this->expires = $value;
                    continue;
                }

                if ( \is_string( $value ) ) {
                    $value = \DateInterval::createFromDateString( $value );
                }

                if ( $value instanceof \DateInterval ) {
                    $this->expires = $value;
                    continue;
                }
            }

            if ( $key === 'if' ) {
                $this->useCache = $value;
            }
        }
    }

    public function print( PrintContext $context ) : string {

        return $context->format(
            <<<'XX'
				echo $this->global->cacheRuntime->get(%dump, %dump, function( $item ) use ( $content ): string { %line
				    $item?->expiresAfter( %dump? );
				    \ob_start();
					%node
					return \ob_get_clean();
				});
				%line;
				XX,
            $this->cacheId,
            $this->useCache,
            $this->position,
            $this->expires,
            $this->content,
            $this->endLine,
        );
    }


    private function normalizeKey( string $value ) : string {

        // Convert to lowercase
        $value = \strtolower( $value );

        // Replace non-alphanumeric characters with the separator
        $value = \preg_replace( "/[^a-z0-9-]+/i", '-', $value );

        // Remove leading and trailing separators
        return \trim( $value, '-' );
    }

    private function hashKey( mixed $value ) : string {
        $data = \json_encode( $value ) ?: \serialize( $value );
        return \hash( 'xxh3', $data );
    }

    public function &getIterator() : \Generator {
        yield $this->args;
        yield $this->content;
    }
}