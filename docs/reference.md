# Block Library reference

<!-- prettier-ignore-start -->

Developer reference for `capell-app/block-library`. Start with the [README](../README.md) for what the extension does and how to install it.

## Package Details

Block Library is an **Available**, **No schema impact** Capell package in the **Capell Essentials** product group. It ships as `capell-app/block-library` and extends these surfaces: admin, frontend, shared.

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/block-library`
- Namespace: `Capell\BlockLibrary`
- Theme key: not applicable

## Technical Shape

### Service providers

- `Capell\BlockLibrary\Providers\BlockLibraryServiceProvider`

### Filament classes

- `AbstractCatalogBuilderBlock`
- `AccordionBlock`
- `BuilderBlockMediaFields`
- `CallToActionBlock`
- `ComparisonBlock`
- `ContentBlock`
- `CounterBlock`
- `DividerBlock`
- `FaqBlock`
- `FeaturesBlock`
- `HeroBlock`
- `LogosBlock`
- `PricingBlock`
- `StatsBlock`
- `TableBlock`
- `TabsBlock`
- `TeamBlock`
- `TestimonialBlock`
- `TimelineBlock`

### Extension contracts

- `BlockDefinitionProvider`
- `BlockDemoContentProvider`
- `BlockFixtureProvider`
- `BlockRenderer`
- `FilamentBuilderBlock`

### Actions

- `BuildBuilderBlockRenderDataAction`
- `ListBlockDefinitionsAction`
- `ListBuilderBlockPickerItemsAction`
- `RegisterBlockDefinitionProviderAction`
- `ResolveBlockDefinitionAction`
- `ResolveBuilderBlockMediaAction`
- `SanitizeBlockHtmlAction`
- `SanitizeBuilderBlockHtmlAction`
- `SeparateClonedBuilderBlockMediaAction`
- `ValidateDefaultBlockCatalogAction`

### Data objects

- `AdminPreviewBlockViewReference`
- `BlockAccessibilityContractData`
- `BlockCompatibilityData`
- `BlockContentContractData`
- `BlockDefinitionData`
- `BlockFixtureData`
- `BlockScreenshotData`
- `BlockSettingDefinitionData`
- `BlockVariantData`
- `BlockVariantKey`
- `BuilderBlockPickerItemData`
- `BuilderBlockRenderData`
- `PublicBlockPresentationData`
- `PublicBlockViewReference`

### Manifest action API

- `sanitizeBlockHtml: Capell\BlockLibrary\Actions\SanitizeBlockHtmlAction`

### Health checks

- `Capell\BlockLibrary\Health\BlockLibraryHealthCheck`

### Blade views

- `packages/block-library/resources/views/blocks/catalog/accordion.blade.php`
- `packages/block-library/resources/views/blocks/catalog/call-to-action.blade.php`
- `packages/block-library/resources/views/blocks/catalog/comparison.blade.php`
- `packages/block-library/resources/views/blocks/catalog/content.blade.php`
- `packages/block-library/resources/views/blocks/catalog/counter.blade.php`
- `packages/block-library/resources/views/blocks/catalog/divider.blade.php`
- `packages/block-library/resources/views/blocks/catalog/faq.blade.php`
- `packages/block-library/resources/views/blocks/catalog/features.blade.php`
- `packages/block-library/resources/views/blocks/catalog/hero.blade.php`
- `packages/block-library/resources/views/blocks/catalog/logos.blade.php`
- `packages/block-library/resources/views/blocks/catalog/pricing.blade.php`
- `packages/block-library/resources/views/blocks/catalog/stats.blade.php`
- `packages/block-library/resources/views/blocks/catalog/table.blade.php`
- `packages/block-library/resources/views/blocks/catalog/tabs.blade.php`
- `packages/block-library/resources/views/blocks/catalog/team.blade.php`
- `packages/block-library/resources/views/blocks/catalog/testimonial.blade.php`
- `packages/block-library/resources/views/blocks/catalog/timeline.blade.php`
- `packages/block-library/resources/views/blocks/fallback.blade.php`
- `packages/block-library/resources/views/blocks/variants/default.blade.php`
- `packages/block-library/resources/views/builder/accordion.blade.php`
- `packages/block-library/resources/views/builder/call-to-action.blade.php`
- `packages/block-library/resources/views/builder/comparison.blade.php`
- `packages/block-library/resources/views/builder/counter.blade.php`
- `packages/block-library/resources/views/builder/divider.blade.php`
- `packages/block-library/resources/views/builder/faq.blade.php`
- `packages/block-library/resources/views/builder/features.blade.php`
- `packages/block-library/resources/views/builder/hero.blade.php`
- `packages/block-library/resources/views/builder/logos.blade.php`
- `packages/block-library/resources/views/builder/pricing.blade.php`
- `packages/block-library/resources/views/builder/stats.blade.php`
- `packages/block-library/resources/views/builder/table.blade.php`
- `packages/block-library/resources/views/builder/tabs.blade.php`
- `packages/block-library/resources/views/builder/team.blade.php`
- `packages/block-library/resources/views/builder/testimonial.blade.php`
- `packages/block-library/resources/views/builder/timeline.blade.php`
- `packages/block-library/resources/views/registry.blade.php`

### Cache tags

- `block-library`


## Data Model

This package has no schema impact. It registers runtime behaviour through `Capell\BlockLibrary\Providers\BlockLibraryServiceProvider` while persistence remains with Capell core or required packages.

<!-- prettier-ignore-end -->
