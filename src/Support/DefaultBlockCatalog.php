<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

use Capell\BlockLibrary\Data\BlockAccessibilityContractData;
use Capell\BlockLibrary\Data\BlockScreenshotData;

final class DefaultBlockCatalog
{
    /**
     * @var array<string, array{view: string, screenshots: list<string>}>
     */
    private const array BLOCKS = [
        'accordion' => ['view' => 'accordion', 'screenshots' => ['admin', 'frontend']],
        'call_to_action' => ['view' => 'call-to-action', 'screenshots' => ['admin', 'frontend']],
        'comparison' => ['view' => 'comparison', 'screenshots' => ['admin', 'frontend']],
        'content' => ['view' => 'content', 'screenshots' => ['admin', 'frontend']],
        'counter' => ['view' => 'counter', 'screenshots' => ['admin', 'frontend']],
        'divider' => ['view' => 'divider', 'screenshots' => ['admin', 'frontend']],
        'faq' => ['view' => 'faq', 'screenshots' => ['admin', 'frontend']],
        'features' => ['view' => 'features', 'screenshots' => ['admin', 'frontend']],
        'hero' => ['view' => 'hero', 'screenshots' => ['admin', 'frontend']],
        'logos' => ['view' => 'logos', 'screenshots' => ['admin', 'frontend']],
        'pricing' => ['view' => 'pricing', 'screenshots' => ['admin', 'frontend']],
        'stats' => ['view' => 'stats', 'screenshots' => ['admin', 'frontend']],
        'table' => ['view' => 'table', 'screenshots' => ['admin', 'frontend']],
        'tabs' => ['view' => 'tabs', 'screenshots' => ['admin', 'frontend']],
        'team' => ['view' => 'team', 'screenshots' => ['admin', 'frontend']],
        'testimonial' => ['view' => 'testimonial', 'screenshots' => ['admin', 'frontend']],
        'timeline' => ['view' => 'timeline', 'screenshots' => ['admin', 'frontend']],
    ];

    /**
     * @return array<string, array{view: string, screenshots: list<string>}>
     */
    public static function blocks(): array
    {
        return self::BLOCKS;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::BLOCKS);
    }

    public static function viewName(string $key): string
    {
        return 'capell-block-library::blocks.catalog.' . self::viewSlug($key);
    }

    public static function label(string $key): string
    {
        return __('capell-block-library::blocks.catalog.' . $key . '.label');
    }

    public static function description(string $key): string
    {
        return __('capell-block-library::blocks.catalog.' . $key . '.description');
    }

    /**
     * @return list<BlockScreenshotData>
     */
    public static function screenshots(string $key): array
    {
        $screenshots = [];

        foreach (self::BLOCKS[$key]['screenshots'] ?? [] as $surface) {
            $screenshots[] = new BlockScreenshotData(
                path: 'docs/screenshots/block-library-' . $surface . '-' . self::viewSlug($key) . '.png',
                alt: self::label($key) . ' ' . $surface . ' screenshot',
                caption: self::label($key) . ' ' . $surface . ' preview',
            );
        }

        return $screenshots;
    }

    public static function accessibilityContract(string $key): BlockAccessibilityContractData
    {
        $semanticRules = [
            'Render exactly one section landmark with a visible heading when a title is present.',
            'Keep heading hierarchy controlled by the host page; do not emit a hard-coded h1.',
        ];

        $keyboardRules = [
            'Expose links, buttons, tabs, accordions, and disclosures through native focusable controls.',
            'Do not require pointer-only interaction for any public action.',
        ];

        $contrastPairs = [
            'Text foreground and section background tokens meet WCAG AA contrast.',
            'Interactive states preserve visible focus and hover contrast.',
        ];

        $mediaRules = [
            'Images and logos require descriptive alternative text or explicit decorative treatment.',
            'Motion, counters, and decorative dividers remain understandable when animation is unavailable.',
        ];

        if (in_array($key, ['accordion', 'faq', 'tabs'], true)) {
            $keyboardRules[] = 'Interactive panels expose expanded, selected, and labelled state to assistive technology.';
        }

        if (in_array($key, ['comparison', 'pricing', 'table'], true)) {
            $semanticRules[] = 'Grouped rows, columns, plans, or table headers preserve readable relationships.';
        }

        if (in_array($key, ['hero', 'call_to_action', 'features'], true)) {
            $keyboardRules[] = 'Primary and secondary calls to action have clear accessible names.';
        }

        return new BlockAccessibilityContractData(
            semanticRules: $semanticRules,
            keyboardRules: $keyboardRules,
            contrastPairs: $contrastPairs,
            mediaRules: $mediaRules,
        );
    }

    public static function viewSlug(string $key): string
    {
        return self::BLOCKS[$key]['view'] ?? str_replace('_', '-', $key);
    }
}
