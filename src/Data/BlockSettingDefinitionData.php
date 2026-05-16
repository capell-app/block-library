<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

use InvalidArgumentException;

final class BlockSettingDefinitionData
{
    /**
     * @param  array<string, string>  $options
     * @param  array<string, mixed>  $visibleWhen
     * @param  array<int, string>  $allowedVariants
     * @param  array<string, mixed>  $responsiveFallback
     * @param  array<string, mixed>  $accessibilityRules
     */
    public function __construct(
        public readonly string $key,
        public readonly string $labelKey,
        public readonly string $type,
        public readonly mixed $default = null,
        public readonly ?string $helpTextKey = null,
        public readonly array $options = [],
        public readonly string $group = 'default',
        public readonly int $order = 0,
        public readonly array $visibleWhen = [],
        public readonly array $allowedVariants = [],
        public readonly array $responsiveFallback = [],
        public readonly array $accessibilityRules = [],
    ) {
        foreach ([
            'key' => $this->key,
            'labelKey' => $this->labelKey,
            'type' => $this->type,
        ] as $field => $value) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(sprintf('Block setting [%s] cannot be empty.', $field));
            }
        }
    }
}
