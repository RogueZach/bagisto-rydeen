# URL Key Auto-Generation for Dealer Portal

**Date:** 2026-03-25
**Status:** Approved

## Problem

Bagisto requires a `url_key` attribute for every product. The Rydeen dealer portal uses this key for:

- Product detail routing (`/dealer/catalog/{slug}`)
- Catalog grid product links
- Client-side cart state tracking (`product_url_key`)

Currently, URL keys are only generated client-side via Vue slugify directives when an admin manually types a product name in the admin UI. If the field is skipped, left empty, or products are created through a non-UI path (future: CSV import, API, seeder), the product has no URL key. This causes broken product links in the dealer catalog.

## Decision

**Approach A: Event listener in Dealer EventServiceProvider.** A server-side listener catches all product creation/update paths and auto-generates the URL key when empty. This follows the existing Rydeen event listener pattern (alongside `OrderListener` and `CustomerFlatSync`).

Alternatives considered:
- **Eloquent Observer** — rejected because url_key is stored in `product_attribute_values`, not on the product model. Observer fires before attribute values are processed.
- **Admin route middleware** — rejected because it only covers the admin UI, not future CSV/API import paths.

## Design

### Listener Placement

New class: `packages/Rydeen/Dealer/src/Listeners/ProductListener.php`

Registered in `packages/Rydeen/Dealer/src/Providers/EventServiceProvider.php`:

```
'catalog.product.create.after' => ProductListener::afterSave
'catalog.product.update.after' => ProductListener::afterSave
```

Same handler for both events — identical logic.

### Generation Logic

`afterSave` receives the product model and:

1. **Guard:** Read the product's `url_key` attribute. If non-empty, return early.
2. **Build slug** from `{sku}-{name}`:
   - Get product SKU (always present)
   - Get product name (may be empty — fall back to SKU only)
   - Slugify using Bagisto's algorithm: lowercase, NFKD normalize, strip diacriticals, keep letters/numbers/spaces/hyphens, spaces to hyphens, collapse duplicates, trim edges
3. **Ensure uniqueness:** Query for existing url_keys matching the slug. On collision, append `-1`, `-2`, etc.
4. **Persist:** Save via `ProductAttributeValueRepository::saveValues()`, then trigger flat index refresh.

### URL Key Format

| SKU | Name | Generated URL Key |
|-----|------|-------------------|
| `100100100` | Rydeen Backup Camera | `100100100-rydeen-backup-camera` |
| `100100100` | _(empty)_ | `100100100` |
| `100100100` | Rydeen Backup Camera _(collision)_ | `100100100-rydeen-backup-camera-1` |

### Update Behavior

URL keys are only generated when empty. Once set (manually or auto-generated), they are never overwritten on subsequent updates. This ensures stable URLs — dealers can bookmark product pages without risk of link rot.

### Dealer Portal Integration

No changes to the dealer catalog views, controllers, or routes. The existing code already handles url_key:

- Route `/dealer/catalog/{slug}` resolves via `findBySlug()`
- Catalog grid links use `$product->url_key ?? $product->id`
- Cart JS maps by `product_url_key`

With auto-generation, every product will have a url_key. The `?? $product->id` fallback becomes a safety net rather than the primary path.

## Files Changed

| File | Action |
|------|--------|
| `packages/Rydeen/Dealer/src/Listeners/ProductListener.php` | New |
| `packages/Rydeen/Dealer/src/Providers/EventServiceProvider.php` | Modified — add 2 event bindings |

## Scope Boundaries

- No changes to `vendor/` or `packages/Webkul/`
- No changes to dealer views, controllers, or routes
- No changes to admin product form UI
- No database migrations required (uses existing `product_attribute_values` table)
