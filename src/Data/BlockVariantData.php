<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

final class BlockVariantData
{
    /**
     * @param  array<string, mixed>  $defaultSettings
     * @param  array<int, string>  $allowedSettings
     * @param  array<string, mixed>  $responsiveFallback
     * @param  array<string, mixed>  $accessibilityRules
     */
    public function __construct(
        public readonly BlockVariantKey $key,
        public readonly string $labelKey,
        public readonly ?string $descriptionKey = null,
        public readonly array $defaultSettings = [],
        public readonly array $allowedSettings = [],
        public readonly array $responsiveFallback = [],
        public readonly array $accessibilityRules = [],
    ) {}
}
