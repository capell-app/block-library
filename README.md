# Block Library

<!-- prettier-ignore-start -->

## What This Plugin Adds

Block Library is an **Available**, **No schema impact** Capell package in the **Capell Foundation** product group. It ships as `capell-app/block-library` and extends these surfaces: admin, frontend, shared.

Block Library supplies typed, reusable content-block definitions and matching Filament Builder blocks for Capell content packages.

The package has no standalone admin resource. Editors see its registered block choices inside Builder surfaces provided by consuming packages and can render their matching frontend views.

Evidence: [`capell.json`](capell.json), [`src/Support/DefaultBlockCatalog.php`](src/Support/DefaultBlockCatalog.php), [`src/Providers/BlockLibraryServiceProvider.php`](src/Providers/BlockLibraryServiceProvider.php), [`docs/overview.admin.md`](docs/overview.admin.md), [`docs/screenshots.json`](docs/screenshots.json), [`tests/Feature/BuilderBlockPickerItemsTest.php`](tests/Feature/BuilderBlockPickerItemsTest.php), [`tests/Integration/BuilderBlockDiscoveryTest.php`](tests/Integration/BuilderBlockDiscoveryTest.php).

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/block-library`
- Namespace: `Capell\BlockLibrary`
- Theme key: not applicable

## Why It Matters

**For developers:** BlockDefinitionProvider and FilamentBuilderBlock define the extension boundary, while the block and builder registries resolve definitions without hard-coding them into a consuming package.

**For teams:** Editors can use the same supported block types across package-owned content forms instead of rebuilding common sections for each workflow.

Evidence: [`src/Contracts/BlockDefinitionProvider.php`](src/Contracts/BlockDefinitionProvider.php), [`src/Contracts/FilamentBuilderBlock.php`](src/Contracts/FilamentBuilderBlock.php), [`src/Support/BlockRegistry.php`](src/Support/BlockRegistry.php), [`src/Support/BuilderBlockRegistry.php`](src/Support/BuilderBlockRegistry.php), [`docs/overview.admin.md`](docs/overview.admin.md), [`docs/screenshots.json`](docs/screenshots.json), [`tests/Feature/DefaultBlockCatalogTest.php`](tests/Feature/DefaultBlockCatalogTest.php).

## Screens And Workflow

Screenshot contract: `docs/screenshots.json`.

![Illustrative content block registry list preview](docs/screenshots/block-library-admin-index.png)

![Frontend hero block](docs/screenshots/block-library-frontend-hero.png)

- Illustrative content block registry list preview (frontend, required evidence).
- Content block asset settings (admin, supplementary evidence).
- Frontend hero block (frontend, required evidence).
- Frontend features block (frontend, required evidence).
- Frontend pricing block (frontend, required evidence).

## Technical Shape

- Service providers: `Capell\BlockLibrary\Providers\BlockLibraryServiceProvider`.
- Filament classes: `AbstractCatalogBuilderBlock`, `AccordionBlock`, `CallToActionBlock`, `ComparisonBlock`, `ContentBlock`, `CounterBlock`, `DividerBlock`, `FaqBlock`, `FeaturesBlock`, `HeroBlock`, `LogosBlock`, `PricingBlock`, `and 6 more`.
- Extension contracts: `BlockDefinitionProvider`, `BlockDemoContentProvider`, `BlockFixtureProvider`, `BlockRenderer`, `FilamentBuilderBlock`.
- Actions: `ListBlockDefinitionsAction`, `ListBuilderBlockPickerItemsAction`, `RegisterBlockDefinitionProviderAction`, `ResolveBlockDefinitionAction`, `SanitizeBlockHtmlAction`, `ValidateDefaultBlockCatalogAction`.
- Data objects: `AdminPreviewBlockViewReference`, `BlockAccessibilityContractData`, `BlockCompatibilityData`, `BlockContentContractData`, `BlockDefinitionData`, `BlockFixtureData`, `BlockScreenshotData`, `BlockSettingDefinitionData`, `BlockVariantData`, `BlockVariantKey`, `BuilderBlockPickerItemData`, `PublicBlockPresentationData`, `and 1 more`.
- Manifest action API: `sanitizeBlockHtml: Capell\BlockLibrary\Actions\SanitizeBlockHtmlAction`.
- Health checks: `Capell\BlockLibrary\Health\BlockLibraryHealthCheck`.
- Blade views: `packages/block-library/resources/views/blocks/catalog/accordion.blade.php`, `packages/block-library/resources/views/blocks/catalog/call-to-action.blade.php`, `packages/block-library/resources/views/blocks/catalog/comparison.blade.php`, `packages/block-library/resources/views/blocks/catalog/content.blade.php`, `packages/block-library/resources/views/blocks/catalog/counter.blade.php`, `packages/block-library/resources/views/blocks/catalog/divider.blade.php`, `packages/block-library/resources/views/blocks/catalog/faq.blade.php`, `packages/block-library/resources/views/blocks/catalog/features.blade.php`, `packages/block-library/resources/views/blocks/catalog/hero.blade.php`, `packages/block-library/resources/views/blocks/catalog/logos.blade.php`, `packages/block-library/resources/views/blocks/catalog/pricing.blade.php`, `packages/block-library/resources/views/blocks/catalog/stats.blade.php`, `and 7 more`.
- Cache tags: `block-library`.

## Data Model

This package has no schema impact. It registers runtime behaviour through `Capell\BlockLibrary\Providers\BlockLibraryServiceProvider` while persistence remains with Capell core or required packages.

## Install Impact

- Required packages: `capell-app/admin`, `capell-app/core`.
- Admin navigation: no admin page or resource contribution is declared.
- Admin/editor extensions: none declared.
- Permissions: none declared in `capell.json`.
- Public routes: none declared.
- Database changes: no package migrations declared.
- Config: no package config files.
- Settings: no package settings declared.
- Queues or schedules: none declared.
- Cache tags: `block-library`.
- Commands: none declared.

## Common Pitfalls

- Keep required Capell packages on compatible v4 releases: `capell-app/admin`, `capell-app/core`.
- Keep public Blade and cached HTML free of authoring markers, model IDs, permissions, signed editor URLs, and lazy database queries.
- Custom write integrations must preserve invalidation for `block-library` cache tags.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Public output leaks unexpected state | Render data, cache variation, or authoring boundary has regressed | Check public Blade, cache tags, and public-output safety tests | Move data loading out of Blade and rerun the package public-output tests |

## Quick Start

1. Install the package: `composer require capell-app/block-library`.
2. No package-specific setup command or migrations are declared.
3. Open `/screenshot-fixtures/catalogue/block-library/content-block-registry-list` and confirm the public output renders without admin state.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Troubleshooting](#troubleshooting)
- [Screenshot contract](docs/screenshots.json)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Related packages: [Content Sections](../content-sections/README.md), [Theme Foundation](../theme-foundation/README.md).
- Focused tests: `vendor/bin/pest packages/block-library/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
