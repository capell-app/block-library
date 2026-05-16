<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use InvalidArgumentException;

final class BlockFixtureData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly array $payload,
    ) {
        throw_if(trim($this->key) === '', InvalidArgumentException::class, 'Block fixture key cannot be empty.');

        throw_if(trim($this->label) === '', InvalidArgumentException::class, 'Block fixture label cannot be empty.');
    }
}
