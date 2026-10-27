<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Data;

final readonly class BuilderBlockRenderData
{
    /** @param array<string, mixed> $meta */
    public function __construct(
        public string $title,
        public string $summary,
        public string $url,
        public string $linkText,
        public array $meta,
    ) {}
}
