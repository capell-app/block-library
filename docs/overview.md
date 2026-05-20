# Content Blocks

Status: **Available, shared foundation** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **shared** · Product group: **Capell Foundation**

Content Blocks provides typed block definitions, registries, renderer contracts, fixtures, demo-content contracts, and Filament builder block discovery for packages that contribute reusable content blocks.

## What This Package Adds

- Block definition data objects and manifest metadata.
- Registry actions for registering, listing, and resolving block definitions.
- Contracts for block definition providers, renderers, fixtures, demo content, and Filament builder blocks.
- Builder block discovery for classes implementing `FilamentBuilderBlock`.
- A fallback block Blade view for unrenderable or unknown block output.

## Install Flow

- Composer package: `capell-app/content-blocks`
- Repository directory: `packages/block-library`
- Hard dependencies: `capell-app/core`
- Optional dependencies: `capell-app/content-sections`, `capell-app/foundation-theme`
- Run `capell:extension-install capell-app/content-blocks` after Composer install when validating package-installed guards.

## Admin Surfaces

This package adds no standalone Filament navigation item, resource, page, widget, relation manager, or settings screen. Admin visibility appears through consuming packages that register block definitions or Filament builder blocks.

## Frontend Surfaces

This package adds no standalone public route. Frontend visibility appears through consuming packages and through the fallback block view when a block renderer cannot resolve a normal output view.

## Screenshot Plan

- Block registry manifest or diagnostics output from a consuming harness.
- Filament builder block picker in a consuming package.
- Fallback block rendering state.

## Known Risks

- Screenshots cannot be meaningful with this package alone because it is a shared foundation package; final captures should install a consuming package such as Content Sections only when documenting the consuming surface.

## Feature Suggestions

- Add a lightweight diagnostics command that lists registered block definitions, providers, renderers, and missing views.
- Add a developer-facing preview page gated to admins that renders each registered block fixture from the registry.
- Add schema validation output for block definitions so consuming packages can catch missing labels, categories, preview views, and accessibility metadata.
