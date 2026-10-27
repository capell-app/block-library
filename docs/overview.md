# Block Library

<!-- prettier-ignore-start -->

## What it does

Block Library supplies 17 reusable Foundation blocks for page layouts: accordion, call to action, comparison, content, counter, divider, FAQ, features, hero, logos, pricing, stats, table, tabs, team, testimonial, and timeline.

## Where it shows up

Open a page's layout or block editor and use the block picker. The picker shows each block's label, description, and icon, and can be searched by terms such as "CTA", "pricing", or "FAQ".

There is no separate Block Library admin screen, permission, or settings page. Once the package is installed, its blocks are registered automatically for editors who can already edit the page.

## What you can do

- Search for a block by its name or purpose.
- Add it to a supported page layout and complete the fields offered by that block.
- Reorder or remove it through the host page editor.
- Preview the page to check the theme's public treatment of the block.

## Logo and team images

Use **Block images** to upload and save images, then choose an image for each logo or team member. Images belong to the content translation, matching existing content-widget uploads, so translated content keeps its own media. Each block has a separate collection. Cloning a logo or team block copies its saved images and image selections into a new collection; removing a clone image leaves the original intact. Missing or unavailable images use the name or initial fallback.

Discarded clones and removed blocks retain their media rows, metadata and files until a retention-aware cleanup exists. Cleanup must never delete media still referenced by saved content, page revisions or another editor's in-flight changes. Failed clone operations remove only the media they created themselves.

Saved builder blocks render through Layout Builder's public Blade registry. The render action allowlists fields, normalises repeater ordering and hydrates public image URLs and alt text before the catalogue view renders. Page, Article, Event and other Pageable layouts prepare block media together before Blade renders. Credential-bearing and signed image or link URLs are excluded, including inside rich text. Inertia builder-block rendering is not included.

## Good to know

- Blocks added by other packages appear alongside the Foundation catalog only when those packages are installed and registered.
- Rich text is sanitised before public rendering. Unsupported scripts, iframes, event attributes, and unsafe links are removed rather than published.
- Interactive blocks use public controls without exposing editor markers or admin state. Always check the public preview after changing tabs, accordions, tables, media, or calls to action.
- If a Foundation block is missing, clear the picker search first. If it is still absent, ask an administrator to check Block Library and the host page editor installation.

---

For how to use Block Library, see the [admin guide](admin-guide.md).
For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->
