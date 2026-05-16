<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

final class BlockAccessibilityContractData
{
    /**
     * @param  array<int, string>  $semanticRules
     * @param  array<int, string>  $keyboardRules
     * @param  array<int, string>  $contrastPairs
     * @param  array<int, string>  $mediaRules
     */
    public function __construct(
        public readonly array $semanticRules = [],
        public readonly array $keyboardRules = [],
        public readonly array $contrastPairs = [],
        public readonly array $mediaRules = [],
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public function toArray(): array
    {
        return [
            'semanticRules' => $this->semanticRules,
            'keyboardRules' => $this->keyboardRules,
            'contrastPairs' => $this->contrastPairs,
            'mediaRules' => $this->mediaRules,
        ];
    }
}
