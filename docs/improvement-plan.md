# Block Library - Improvement & Growth Plan

> Package: capell-app/block-library · Kind: package · Tier: free · Product group: Capell Foundation · Bundle: foundation · Status: Active

## 1. Snapshot

Block Library provides reusable content blocks, typed block definitions, public/admin view references, fixtures, screenshot metadata, Filament Builder block discovery, package-author integration guidance, accessibility contracts, and a default catalog of 17 block types. It is a foundational package for content composition and public-safe block rendering. Tests cover registry behavior, manifest compilation, discovery, default catalog rendering, accessibility contracts, and docs discoverability. The remaining Next row focuses on fixture/demo completeness.

## 2. Improvements (existing functionality)

1. **Implement catalog health diagnostics.** `BlockLibraryHealthCheck` only reports compatibility. Add checks for registry binding, default provider registration, catalog keys, required translations, public views, preview/admin views, and screenshot file existence. Evidence: `src/Health/BlockLibraryHealthCheck.php`, `src/Support/DefaultBlockCatalog.php`, `resources/views/blocks/catalog`. - **M**

2. **Add public-output safety coverage for every catalog block.** Existing tests render representative blocks, but each public catalog view should have no authoring markers, signed editor URLs, public Blade queries, or package metadata leakage. Evidence: `tests/Feature/DefaultBlockCatalogTest.php`, `resources/views/blocks/catalog/*.blade.php`. - **M**

3. **Harden builder block discovery cache invalidation.** `BuilderBlockDiscovery` restores cached blocks when present and only clears manually. Add tests and docs for cache clearing when packages are installed/removed or block files change. Evidence: `src/Support/BuilderBlockDiscovery.php`, `tests/Integration/BuilderBlockDiscoveryTest.php`. - **M**

4. **Align marketplace gallery with the full catalog.** The package has many committed admin/frontend screenshots, but `capell.json` promotes only seven. Either document the curated subset or promote a broader, tested gallery. Evidence: `docs/screenshots.json`, `docs/screenshots/`, `capell.json marketplace.screenshots`. - **S**

## 3. Missing Features (gaps)

Capabilities declared: `content-blocks`, `filament-builder-blocks`, and `safe-public-block-views`.

- **No actionable health report.** Installers cannot tell which block/view/translation is missing.
- **Done/Shipped: per-block accessibility gate.** Default catalog definitions now carry complete `BlockAccessibilityContractData` buckets and package health fails when semantics, keyboard, contrast, or media rules are omitted.
- **No block versioning/deprecation path.** Consuming layouts need a way to evolve block schema safely.
- **Done/Shipped: consumer-facing integration guide.** `docs/custom-blocks.md` documents custom definitions, provider tagging, public-safe views, fixtures, builder blocks, screenshots, and Marketplace alignment for package authors.

## 4. Issues / Risks

1. **Important gap: health is a placeholder for a shared foundation package.** Recommended fix: catalog diagnostics and tests. - **P2**

2. **Important risk: public block views can regress into authoring leakage or Blade queries.** Recommended fix: matrix safety tests across all default public views. - **P2**

3. **Important risk: discovery cache can serve stale block maps after package changes.** Recommended fix: cache invalidation rules and tests. - **P2**

4. **Improvement: screenshot/gallery policy is unclear.** Recommended fix: curated gallery docs or broader tested promotion. - **P3**

## 5. Marketplace & Positioning

Block Library should be positioned as the reusable content-block foundation for Capell package authors and admins. For teams, it offers a consistent block catalog. For developers, it provides typed definitions, fixtures, screenshots, Builder integration, and public-safe view contracts.

**Current summary:** "Block Library provides reusable content blocks, typed definitions, screenshots, and Filament Builder blocks for Capell content packages."

**Improved summary:** "A reusable Capell block catalog and developer contract for safe public block views, Filament Builder blocks, fixtures, screenshots, and typed definitions."

**Media status:** Existing screenshot volume is strong. Decide whether the Marketplace gallery remains curated or promotes the full runner-backed catalog.

**Cross-sell:** Layout Builder, Content Sections, Foundation Theme, theme packages, Demo Kit, and package authors building content tools.

## 6. Prioritized Roadmap

| Item                                                      | Bucket | Effort | Impact | Section ref |
| --------------------------------------------------------- | ------ | ------ | ------ | ----------- |
| Add catalog health diagnostics                            | Done   | M      | High   | §2.1, §4.1  |
| Add public-output safety matrix for every catalog block   | Done   | M      | High   | §2.2, §4.2  |
| Add builder block discovery cache invalidation tests/docs | Done   | M      | Medium | §2.3, §4.3  |
| Document/promote marketplace screenshot gallery policy    | Done   | S      | Medium | §2.4, §4.4  |
| Enforce per-block accessibility contracts                 | Done   | M      | High   | §3          |
| Add custom block integration guide for package authors    | Done   | S      | Medium | §3, §5      |
| Add fixture/demo provider completeness checks             | Next   | M      | Medium | §3          |
| Add block schema versioning/deprecation support           | Later  | L      | Medium | §3          |
| Add richer admin block picker/search UX                   | Later  | M      | Medium | §5          |

## 7. Verification

Implementation slices shipped the current Now rows. Re-run the package verification with:

```bash
vendor/bin/pest packages/block-library/tests --configuration=phpunit.xml
```

For catalog or discovery changes, include:

```bash
vendor/bin/pest packages/block-library/tests/Feature/DefaultBlockCatalogTest.php packages/block-library/tests/Integration/BuilderBlockDiscoveryTest.php --configuration=phpunit.xml
```

For accessibility-contract changes, include:

```bash
vendor/bin/pest packages/block-library/tests/Feature/DefaultBlockCatalogTest.php packages/block-library/tests/Feature/BlockLibraryHealthCheckTest.php --configuration=phpunit.xml
```

For custom block integration docs, include:

```bash
vendor/bin/pest packages/block-library/tests/Unit/ManifestRequirementsTest.php --configuration=phpunit.xml
```

## 8. Completion Checklist

- [x] Package plan created from current code, manifest, docs, screenshots, and tests.
- [x] Comprehensive local review pass completed for provider, registry, discovery, default catalog, docs, screenshots, and tests.
- [x] Capell audience pass completed for admins, package authors, and theme builders.
- [x] Approved implementation slices shipped.
- [x] Focused Block Library verification passed.
- [x] Package tests passed.
- [x] Repo preflight passed for changed files.
