---
name: capell-content-blocks-development
description: Use when editing reusable Capell Content Blocks or their Mosaic assets.
---

# Capell Content Blocks

Reusable content records rendered through Mosaic-style assets and configurators.

## Look

- `packages/content-blocks/src`
- `packages/content-blocks/docs`
- `packages/content-blocks/README.md`

## Rules

- Keep blocks reusable; avoid page-specific content logic.
- Put creation, replication, and form mutation behaviour in Actions.
- Treat asset relation managers as Mosaic integration, not standalone rendering.
- Run `vendor/bin/pest packages/content-blocks/tests`.
