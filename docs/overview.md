# Block Library

Status: **Available, shared foundation** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend, shared** · Product group: **Capell Foundation**

Block Library provides typed block definitions, reusable foundation block views, screenshots, renderer contracts, fixtures, demo-content contracts, and Filament Builder block discovery for packages that contribute reusable content blocks.

## What This Package Adds

- Block definition data objects and manifest metadata.
- Registry actions for registering, listing, and resolving block definitions.
- Contracts for block definition providers, renderers, fixtures, demo content, and Filament builder blocks.
- Builder block discovery for classes implementing `FilamentBuilderBlock`.
- Public Blade views and screenshots for the default block catalog.
- A fallback block Blade view for unrenderable or unknown block output.

## Install Flow

- Composer package: `capell-app/block-library`
- Repository directory: `packages/block-library`
- Hard dependencies: `capell-app/admin`, `capell-app/core`
- Optional dependencies: `capell-app/content-sections`, `capell-app/foundation-theme`
- Run `capell:extension-install capell-app/block-library` after Composer install when validating package-installed guards.

## Admin Surfaces

This package adds no standalone Filament navigation item, resource, page, widget, relation manager, or settings screen. Admin visibility appears through consuming packages that place the registered Filament Builder blocks into a Builder field.

## Frontend Surfaces

This package adds no standalone public route. Frontend visibility appears through consuming packages that render registered block views and through the fallback block view when a block renderer cannot resolve a normal output view.

## Screenshot Plan

- Admin and frontend screenshots exist for every default catalog block in `docs/screenshots/`.
- A Filament Builder block picker capture should come from a consuming admin Builder field.
- Fallback block rendering state should remain a controlled fixture capture.

## Known Risks

- The package owns block screenshots but not a standalone preview route. Final captures still need a consuming harness or admin Builder field.

## Feature Suggestions

- Add a lightweight diagnostics command that lists registered block definitions, providers, renderers, and missing views.
- Add a developer-facing preview page gated to admins that renders each registered block fixture from the registry.
- Add schema validation output for block definitions so consuming packages can catch missing labels, categories, preview views, and accessibility metadata.
