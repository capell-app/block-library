<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use InvalidArgumentException;

final class BlockScreenshotData
{
    public function __construct(
        public readonly string $path,
        public readonly string $alt,
        public readonly string $caption,
        public readonly ?string $variant = null,
    ) {
        foreach ([
            'path' => $this->path,
            'alt' => $this->alt,
            'caption' => $this->caption,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(sprintf('Block screenshot [%s] cannot be empty.', $field));
            }
        }
    }
}
