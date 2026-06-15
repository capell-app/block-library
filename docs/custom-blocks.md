# Custom Block Integration Guide

Package authors can add custom Block Library definitions without changing Core or the Block Library package. Keep the block contract in your package and register it through the shared provider tag.

## Definition Provider

Create a provider that implements `Capell\BlockLibrary\Contracts\BlockDefinitionProvider` and yields `BlockDefinitionData` instances:

```php
<?php

declare(strict_types=1);

namespace Vendor\Package\Blocks;

use Capell\BlockLibrary\Contracts\BlockDefinitionProvider;
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

## Fixtures And Demo Content

Use `BlockFixtureProvider` when a block needs reusable example payloads for tests, docs, or seeded demos. Use `BlockDemoContentProvider` when the package needs richer demo content for a full installed site. Both providers stay package-owned and are referenced from `BlockDefinitionData`.

Fixtures should be small, deterministic, and safe to render publicly. Do not store theme wrappers, utility classes, or complex layout HTML in fixture payloads.

## Builder Blocks

If the block should appear in Filament Builder, add a class implementing `FilamentBuilderBlock`. For catalog-style blocks, mirror the local `AbstractCatalogBuilderBlock` pattern: keep the schema thin, delegate content structure to typed settings, and keep runtime rendering in public views.

When package files change or an extension is installed/removed, clear `BuilderBlockDiscovery` cache so stale builder maps are not reused.

## Screenshots And Marketplace

Add entries to your package `docs/screenshots.json` for admin and public block states that matter to buyers or package authors. Promote only committed, runner-backed, visually representative screenshots in `capell.json` marketplace media.

For every custom block definition, keep these aligned:

- `BlockDefinitionData` key, label, description, public view, source package.
- Translation keys used by labels, descriptions, defaults, and settings.
- Public view tests for output safety.
- Fixture/demo provider coverage when fixtures are declared.
- Screenshot contract entries for admin/builder and public render states.
- Builder block registration or discovery cache invalidation tests when Filament Builder support is included.
