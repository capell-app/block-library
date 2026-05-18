<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

final class BlockContentContractData
{
    /**
     * @param  array<int, string>  $requiredFields
     * @param  array<int, string>  $optionalFields
     * @param  array<int, string>  $imageRatios
     * @param  array<int, string>  $accessibilityRules
     */
    public function __construct(
        public readonly array $requiredFields = [],
        public readonly array $optionalFields = [],
        public readonly ?int $maxItems = null,
        public readonly array $imageRatios = [],
        public readonly bool $requiresCta = false,
        public readonly bool $allowEmptyCta = true,
        public readonly array $accessibilityRules = [],
    ) {}
}
