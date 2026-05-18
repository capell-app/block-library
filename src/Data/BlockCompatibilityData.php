<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Data;

final class BlockCompatibilityData
{
    /**
     * @param  array<int, string>  $themeKeys
     * @param  array<int, string>  $unsupportedThemeKeys
     * @param  array<int, string>  $requiredPackages
     */
    public function __construct(
        public readonly array $themeKeys = [],
        public readonly array $unsupportedThemeKeys = [],
        public readonly array $requiredPackages = [],
        public readonly bool $requiresAccessibleTokenPairs = true,
    ) {}

    public function supportsTheme(?string $themeKey): bool
    {
        if ($themeKey === null || $themeKey === '') {
            return true;
        }

        if (in_array($themeKey, $this->unsupportedThemeKeys, true)) {
            return false;
        }

        return $this->themeKeys === [] || in_array($themeKey, $this->themeKeys, true);
    }
}
