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
        if (trim($this->key) === '') {
            throw new InvalidArgumentException('Block fixture key cannot be empty.');
        }

        if (trim($this->label) === '') {
            throw new InvalidArgumentException('Block fixture label cannot be empty.');
        }
    }
}
