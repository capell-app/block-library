<?php

declare(strict_types=1);

namespace Capell\BlockLibrary\Support;

use Capell\BlockLibrary\Data\BlockAccessibilityContractData;
use Capell\BlockLibrary\Data\BlockScreenshotData;
use Filament\Support\Icons\Heroicon;

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

    public static function icon(string $key): Heroicon
    {
        return match ($key) {
            'accordion' => Heroicon::OutlinedBars3BottomLeft,
            'call_to_action' => Heroicon::OutlinedMegaphone,
            'comparison' => Heroicon::OutlinedScale,
            'content' => Heroicon::OutlinedDocumentText,
            'counter' => Heroicon::OutlinedCalculator,
            'divider' => Heroicon::OutlinedMinus,
            'faq' => Heroicon::OutlinedQuestionMarkCircle,
            'features' => Heroicon::OutlinedSquares2x2,
            'hero' => Heroicon::OutlinedSparkles,
            'logos' => Heroicon::OutlinedBuildingOffice2,
            'pricing' => Heroicon::OutlinedBanknotes,
            'stats' => Heroicon::OutlinedChartBar,
            'table' => Heroicon::OutlinedTableCells,
            'tabs' => Heroicon::OutlinedRectangleGroup,
            'team' => Heroicon::OutlinedUserGroup,
            'testimonial' => Heroicon::OutlinedChatBubbleLeftRight,
            'timeline' => Heroicon::OutlinedClock,
            default => Heroicon::OutlinedSquare3Stack3d,
        };
    }

    /**
     * @return list<string>
     */
    public static function searchTerms(string $key): array
    {
        $terms = [
            $key,
            str_replace('_', ' ', $key),
            self::label($key),
            self::description($key),
            'foundation',
            'content block',
        ];

        $terms = [
            ...$terms,
            ...match ($key) {
                'accordion' => ['collapsible', 'disclosure', 'sections'],
                'call_to_action' => ['cta', 'button', 'conversion'],
                'comparison' => ['compare', 'columns', 'features'],
                'content' => ['copy', 'article', 'text'],
                'counter' => ['metrics', 'number', 'kpi'],
                'divider' => ['separator', 'rule', 'spacing'],
                'faq' => ['questions', 'answers', 'support'],
                'features' => ['benefits', 'grid', 'cards'],
                'hero' => ['banner', 'headline', 'intro'],
                'logos' => ['brands', 'partners', 'trust'],
                'pricing' => ['plans', 'tiers', 'commerce'],
                'stats' => ['metrics', 'numbers', 'proof'],
                'table' => ['rows', 'columns', 'comparison'],
                'tabs' => ['sections', 'switcher', 'panels'],
                'team' => ['people', 'members', 'profiles'],
                'testimonial' => ['quote', 'review', 'social proof'],
                'timeline' => ['milestones', 'history', 'steps'],
                default => [],
            },
        ];

        return array_values(array_unique(array_map(
            static fn (string $term): string => strtolower(trim($term)),
            array_filter($terms, static fn (string $term): bool => trim($term) !== ''),
        )));
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

    /**
     * @return array{asset: null, title: string, summary: string, meta: array<string, mixed>, url?: string}
     */
    public static function fixturePayload(string $key): array
    {
        return [
            'asset' => null,
            'title' => 'Preview',
            'summary' => '<p>Reusable public output.</p>',
            'url' => '#',
            'meta' => match ($key) {
                'accordion' => [
                    'first_open' => true,
                    'items' => [
                        ['heading' => 'Question one', 'content' => '<p>Answer one.</p>'],
                    ],
                ],
                'call_to_action' => [
                    'alignment' => 'center',
                    'actions' => [
                        ['label' => 'Start', 'url' => '#'],
                    ],
                ],
                'comparison' => [
                    'columns' => [
                        ['heading' => 'Standard', 'description' => 'Default plan.'],
                        ['heading' => 'Pro', 'description' => 'Advanced plan.', 'highlighted' => true],
                    ],
                    'rows' => [
                        ['label' => 'Blocks', 'values' => '17|17'],
                    ],
                ],
                'counter' => [
                    'counters' => [
                        ['value' => '17', 'suffix' => '+', 'label' => 'Blocks', 'description' => 'Default catalog blocks.'],
                    ],
                ],
                'divider' => [
                    'style' => 'dots',
                ],
                'faq' => [
                    'first_open' => true,
                    'questions' => [
                        ['question' => 'Is it public-safe?', 'answer' => '<p>Yes.</p>'],
                    ],
                ],
                'features' => [
                    'columns' => '3',
                    'features' => [
                        ['heading' => 'Typed definitions', 'description' => 'Blocks are registered with typed data.', 'url' => '#'],
                    ],
                ],
                'hero' => [
                    'alignment' => 'center',
                ],
                'logos' => [
                    'columns' => '4',
                    'logos' => [
                        ['name' => 'Capell', 'url' => '#'],
                    ],
                ],
                'pricing' => [
                    'plans' => [
                        [
                            'name' => 'Foundation',
                            'price' => 'Free',
                            'period' => 'site',
                            'description' => 'Included with Capell.',
                            'features' => "Blocks\nBuilder integration",
                            'highlighted' => true,
                            'action_label' => 'Use block',
                            'action_url' => '#',
                        ],
                    ],
                ],
                'stats' => [
                    'columns' => '3',
                    'stats' => [
                        ['value' => '17', 'label' => 'Blocks', 'description' => 'Catalog size.'],
                    ],
                ],
                'table' => [
                    'caption' => 'Catalog coverage',
                    'headers' => [
                        ['label' => 'Block'],
                        ['label' => 'Safe'],
                    ],
                    'rows' => [
                        ['cells' => 'Hero|Yes'],
                    ],
                ],
                'tabs' => [
                    'tabs' => [
                        ['label' => 'Plan', 'content' => '<p>Build with approved blocks.</p>'],
                    ],
                ],
                'team' => [
                    'columns' => '3',
                    'members' => [
                        ['name' => 'Editor', 'role' => 'Publisher', 'bio' => 'Builds pages.', 'url' => '#'],
                    ],
                ],
                'testimonial' => [
                    'quote' => 'Reusable blocks keep public output predictable.',
                    'author' => 'Capell',
                    'role' => 'Package developer',
                ],
                'timeline' => [
                    'milestones' => [
                        ['date' => '2026', 'heading' => 'Catalog', 'description' => 'Default blocks ship.'],
                    ],
                ],
                default => [],
            },
        ];
    }

    public static function viewSlug(string $key): string
    {
        return self::BLOCKS[$key]['view'] ?? str_replace('_', '-', $key);
    }
}
