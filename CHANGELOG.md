# Changelog

All notable changes to `capell-app/block-library` will be documented in this file.

## Unreleased

- Added render-boundary sanitization for every editor-authored rich-text field emitted by the catalog views, including nested accordion, FAQ, and tab content.
- Added default builder block picker metadata with catalog icons, descriptions, searchable keywords, and `ListBuilderBlockPickerItemsAction` for consuming admin surfaces.
- Adapted `ListBuilderBlockPickerItemsAction` to Core Admin's neutral block-picker metadata contract, so the installed Page editor's block picker is searchable and categorised for every default block (CAP-0300).
- Prepared package metadata and documentation for ongoing Capell 0.0.x package work.
