# Custom Block Integration Guide

Package authors can add custom Block Library definitions without changing Core or the Block Library package. Keep the block contract in your package and register it through the shared provider tag.

## Definition Provider

Create a provider that implements `Capell\BlockLibrary\Contracts\BlockDefinitionProvider` and yields `BlockDefinitionData` instances:

```php
<?php

declare(strict_types=1);

namespace Vendor\Package\Blocks;

use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
use Capell\BlockLibrary\Data\BlockAccessibilityContractData;
use Capell\BlockLibrary\Data\BlockDefinitionData;

final class MarketingBlockDefinitionProvider implements BlockDefinitionProvider
{
    public function definitions(): iterable
    {
        yield new BlockDefinitionData(
            key: 'marketing-hero',
            label: __('vendor-package::blocks.marketing_hero.label'),
            description: __('vendor-package::blocks.marketing_hero.description'),
            category: 'marketing',
            view: 'vendor-package::blocks.marketing-hero',
            defaults: [
                'headline' => __('vendor-package::blocks.marketing_hero.default_headline'),
            ],
            safeForPublicOutput: true,
            sourcePackage: 'vendor/package',
            accessibilityContract: new BlockAccessibilityContractData(
                semanticRules: ['Render one section with a visible heading when a title is present.'],
                keyboardRules: ['Expose every public action as a native focusable link or button.'],
                contrastPairs: ['Text and action tokens meet WCAG AA contrast on the section background.'],
                mediaRules: ['Images require descriptive alternative text or explicit decorative treatment.'],
            ),
        );
    }
}
```

Register the provider in your package service provider:

```php
$this->app->tag([
    \Vendor\Package\Blocks\MarketingBlockDefinitionProvider::class,
], \Capell\BlockLibrary\Contracts\BlockDefinitionProvider::TAG);
```

## Public Views

Public block views must be safe for anonymous visitors and cached HTML. They should render from the payload passed to the view and must not query models, lazy-load relationships, expose authoring IDs, or include admin/editor selectors.

Recommended view contract:

- Use package-owned Blade views such as `vendor-package::blocks.marketing-hero`.
- Escape plain strings with Blade echoing.
- Render only portable, intentionally public HTML.
- Keep presentation wrappers in Blade or theme assets, not seeded content fields.
- Add package tests that render every public block view through representative payloads and assert no authoring markers, signed editor URLs, secrets, or package-internal metadata leak.

## Accessibility Contracts

Every public block definition should declare a `BlockAccessibilityContractData` with semantic, keyboard, contrast, and media rules. Treat the contract as the package-owned checklist that keeps public block output usable across themes.

The default catalog health check fails when a shipped block has an empty rule bucket. Custom packages should add the same focused test around their provider output before relying on the block in Layout Builder or Content Sections.

## Fixtures And Demo Content

Use `BlockFixtureProvider` when a block needs reusable example payloads for tests, docs, or seeded demos. Use `BlockDemoContentProvider` when the package needs richer demo content for a full installed site. Both providers stay package-owned and are referenced from `BlockDefinitionData`.

Fixtures should be small, deterministic, and safe to render publicly. Do not store theme wrappers, utility classes, or complex layout HTML in fixture payloads.

The default Block Library catalog uses one provider for both contracts so every shipped block has a renderable fixture and demo payload. Custom packages should add provider completeness tests that instantiate each declared provider, assert at least one non-empty fixture payload, and render the payload through the public view safety test.

## Builder Blocks

If the block should appear in Filament Builder, add a class implementing `FilamentBuilderBlock`. For catalog-style blocks, mirror the local `AbstractCatalogBuilderBlock` pattern: keep the schema thin, delegate content structure to typed settings, and keep runtime rendering in public views.

When package files change or an extension is installed/removed, clear `BuilderBlockDiscovery` cache so stale builder maps are not reused.

## Screenshots And Marketplace

Add entries to your package `docs/screenshots.json` for admin and public block states that matter to buyers or package authors. Promote only committed, runner-backed, visually representative screenshots in `capell.json` marketplace media.

For every custom block definition, keep these aligned:

- `BlockDefinitionData` key, label, description, public view, source package.
- Translation keys used by labels, descriptions, defaults, and settings.
- Public view tests for output safety.
- Fixture/demo provider coverage for every block that declares providers.
- Screenshot contract entries for admin/builder and public render states.
- Builder block registration or discovery cache invalidation tests when Filament Builder support is included.
